<?php
$pageTitle='My Documents';
$activePage='documents.php';
require_once 'includes/auth.php';
require_roles(['employee','hr_officer','admin','superadmin']);
require_once 'includes/data.php';
require_once 'includes/upload.php';
require_once 'includes/notifications.php';

$u=current_user();
$uid=(int)($u['id']??0);
$isReviewer=has_role(['hr_officer','admin','superadmin']);
$reviewUserId=$isReviewer?(int)($_GET['user_id']??0):0;
$cats=[
 'educational'=>['label'=>'Educational Documents','icon'=>'fa-user-graduate'],
 'identification'=>['label'=>'Identification Documents','icon'=>'fa-id-card'],
 'medical'=>['label'=>'Medical Documents','icon'=>'fa-heartbeat'],
 'certificates'=>['label'=>'Certificates & Training','icon'=>'fa-certificate'],
 'others'=>['label'=>'Others','icon'=>'fa-folder-open'],
];

function docs_init(){db()->exec("CREATE TABLE IF NOT EXISTS user_documents(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 category VARCHAR(50) NOT NULL,
 doc_name VARCHAR(255) NOT NULL,
 description TEXT NULL,
 file_path VARCHAR(1024) NOT NULL,
 original_name VARCHAR(255) NOT NULL,
 file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
 mime_type VARCHAR(120) NOT NULL DEFAULT '',
 status ENUM('verified','pending','rejected') NOT NULL DEFAULT 'pending',
 shared_token VARCHAR(64) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_ud_user(user_id), INDEX idx_ud_cat(user_id,category), UNIQUE KEY uq_ud_share(shared_token)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 try{db()->exec("ALTER TABLE user_documents MODIFY status ENUM('verified','pending','rejected') NOT NULL DEFAULT 'pending'");}catch(Throwable $e){}
 db()->exec("CREATE TABLE IF NOT EXISTS user_document_status_logs(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 document_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 changed_by INT UNSIGNED NOT NULL,
 old_status VARCHAR(30) NOT NULL,
 new_status VARCHAR(30) NOT NULL,
 note VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_doc(document_id),
 INDEX idx_user(user_id),
 INDEX idx_changed(changed_by)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
function docs_log_status($docId,$userId,$changedBy,$old,$new,$note=''){
 $st=db()->prepare('INSERT INTO user_document_status_logs(document_id,user_id,changed_by,old_status,new_status,note) VALUES(?,?,?,?,?,?)');
 $st->execute([(int)$docId,(int)$userId,(int)$changedBy,(string)$old,(string)$new,$note!==''?(string)$note:null]);
}
function docs_size($b){$u=['B','KB','MB','GB'];$i=0;$s=max(0,(float)$b);while($s>=1024&&$i<3){$s/=1024;$i++;}return $i?number_format($s,$s>=10?1:2).' '.$u[$i]:((int)$s).' B';}
function docs_label($cats,$c){return $cats[$c]['label']??ucfirst($c);} 
function docs_icon($p,$m=''){ $e=strtolower(pathinfo($p,PATHINFO_EXTENSION)); if(str_starts_with($m,'image/')||in_array($e,['jpg','jpeg','png','webp'],true))return 'fa-file-image'; if($m==='application/pdf'||$e==='pdf')return 'fa-file-pdf'; if(in_array($e,['doc','docx'],true))return 'fa-file-word'; return 'fa-file';}
function docs_inline($m,$p){$e=strtolower(pathinfo($p,PATHINFO_EXTENSION));return str_starts_with($m,'image/')||$m==='application/pdf'||in_array($e,['jpg','jpeg','png','webp','pdf'],true);} 
function docs_send($abs,$name,$in){ if(!is_file($abs)||!is_readable($abs)){http_response_code(404);exit('File not found.');} $m=mime_content_type($abs); if(!$m)$m='application/octet-stream'; header('Content-Type: '.$m); header('Content-Length: '.(string)filesize($abs)); header('Content-Disposition: '.($in?'inline':'attachment').'; filename="'.rawurlencode($name).'"'); readfile($abs); exit; }
function flash_set($s,$m){$_SESSION['docs_flash']=['s'=>$s,'m'=>$m];}
function flash_get(){ $f=$_SESSION['docs_flash']??['s'=>'','m'=>'']; unset($_SESSION['docs_flash']); return $f; }
function go($q=[]){$qs=http_build_query($q); header('Location: documents.php'.($qs?'?'.$qs:'')); exit;}

docs_init();
if($uid<=0){flash_set('error','Unable to resolve user.');go();}

$act=trim((string)($_GET['action']??''));
if($act==='download_all'){
 if($isReviewer){
  if($reviewUserId>0){$st=db()->prepare('SELECT original_name,file_path FROM user_documents WHERE user_id=? ORDER BY created_at DESC');$st->execute([$reviewUserId]);}
  else{$st=db()->query('SELECT original_name,file_path FROM user_documents ORDER BY created_at DESC');}
 }else{$st=db()->prepare('SELECT original_name,file_path FROM user_documents WHERE user_id=? ORDER BY created_at DESC');$st->execute([$uid]);}
 $rows=$st->fetchAll()?:[];
 if(!$rows){flash_set('error','No documents available to download.');go();}
 if(!class_exists('ZipArchive')){flash_set('error','ZIP extension is not available.');go();}
 $tmp=tempnam(sys_get_temp_dir(),'docs_');$zip=new ZipArchive(); if($tmp===false||$zip->open($tmp,ZipArchive::OVERWRITE)!==true){flash_set('error','Unable to create ZIP archive.');go();}
 $i=1; foreach($rows as $r){$a=__DIR__.'/'.ltrim((string)$r['file_path'],'/'); if(!is_file($a))continue; $n=(string)($r['original_name']?:'document_'.$i); $zip->addFile($a,$i.'_'.preg_replace('/[^A-Za-z0-9._-]+/','_',$n)); $i++;}
 $zip->close(); header('Content-Type: application/zip'); header('Content-Length: '.(string)filesize($tmp)); header('Content-Disposition: attachment; filename="my_documents_'.date('Ymd_His').'.zip"'); readfile($tmp); @unlink($tmp); exit;
}
if($act==='view'||$act==='download'){
 $id=(int)($_GET['id']??0);
 if($isReviewer){$st=db()->prepare('SELECT * FROM user_documents WHERE id=? LIMIT 1');$st->execute([$id]);}
 else{$st=db()->prepare('SELECT * FROM user_documents WHERE id=? AND user_id=? LIMIT 1');$st->execute([$id,$uid]);}
 $d=$st->fetch();
 if(!$d){flash_set('error','Document not found.');go();}
 $abs=__DIR__.'/'.ltrim((string)$d['file_path'],'/'); docs_send($abs,(string)$d['original_name'],$act==='view'&&docs_inline((string)$d['mime_type'],(string)$d['file_path']));
}
if(trim((string)($_GET['shared']??''))!==''){
 $t=trim((string)$_GET['shared']);$st=db()->prepare('SELECT * FROM user_documents WHERE shared_token=? LIMIT 1');$st->execute([$t]);$d=$st->fetch(); if(!$d){flash_set('error','Invalid share link.');go();}
 docs_send(__DIR__.'/'.ltrim((string)$d['file_path'],'/'),(string)$d['original_name'],docs_inline((string)$d['mime_type'],(string)$d['file_path']));
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['csrf_token']??'')){flash_set('error','Security token expired.');go();}
 $pa=trim((string)($_POST['action']??''));
 if($pa==='upload_document'||$pa==='update_document'){
  $cat=trim((string)($_POST['docCategory']??''));$name=trim((string)($_POST['docName']??''));$desc=trim((string)($_POST['docDescription']??''));$did=(int)($_POST['document_id']??0);
  if(!isset($cats[$cat])){flash_set('error','Please select a valid category.');go(['open_upload'=>1]);}
  if($name===''){flash_set('error','Document name is required.');go(['open_upload'=>1]);}
  $dir=__DIR__.'/uploads/'.basename($cat); if(!is_dir($dir))@mkdir($dir,0775,true);
  [$ok,$res]=handle_file_upload('docFile',$cat); if(!$ok){flash_set('error','Upload failed: '.$res);go(['open_upload'=>1]);}
  $rel=(string)$res; $abs=__DIR__.'/'.ltrim($rel,'/'); $size=is_file($abs)?(int)filesize($abs):0; $mime=is_file($abs)?(string)mime_content_type($abs):''; $orig=(string)($_FILES['docFile']['name']??basename($rel));
  if($pa==='upload_document'){
   db()->prepare('INSERT INTO user_documents(user_id,category,doc_name,description,file_path,original_name,file_size,mime_type,status) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$uid,$cat,$name,$desc,$rel,$orig,$size,$mime,'pending']);
   create_notification($uid,'document_upload','Document uploaded',$name.' was uploaded successfully.','documents.php');
   flash_set('success','Document uploaded successfully.');go();
  }
  $st=db()->prepare('SELECT file_path FROM user_documents WHERE id=? AND user_id=? LIMIT 1');$st->execute([$did,$uid]);$old=$st->fetch();
  if(!$old){@unlink($abs);flash_set('error','Document to update was not found.');go();}
  db()->prepare('UPDATE user_documents SET category=?,doc_name=?,description=?,file_path=?,original_name=?,file_size=?,mime_type=?,status=?,shared_token=NULL,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$cat,$name,$desc,$rel,$orig,$size,$mime,'pending',$did,$uid]);
  $oa=__DIR__.'/'.ltrim((string)$old['file_path'],'/'); if(is_file($oa)&&realpath($oa)!==realpath($abs))@unlink($oa);
  create_notification($uid,'document_update','Document updated',$name.' was updated successfully.','documents.php');
  flash_set('success','Document updated successfully.');go();
 }
 if($pa==='delete_document'){
  $id=(int)($_POST['document_id']??0);$st=db()->prepare('SELECT doc_name,file_path FROM user_documents WHERE id=? AND user_id=? LIMIT 1');$st->execute([$id,$uid]);$d=$st->fetch();
  if(!$d){flash_set('error','Document not found.');go();}
  db()->prepare('DELETE FROM user_documents WHERE id=? AND user_id=?')->execute([$id,$uid]);
  $a=__DIR__.'/'.ltrim((string)$d['file_path'],'/'); if(is_file($a))@unlink($a);
  create_notification($uid,'document_delete','Document deleted',(string)$d['doc_name'].' was deleted.','documents.php');
  flash_set('success','Document deleted successfully.');go();
 }
 if($pa==='share_document'){
  $id=(int)($_POST['document_id']??0);$tok=bin2hex(random_bytes(16));$st=db()->prepare('UPDATE user_documents SET shared_token=?,updated_at=NOW() WHERE id=? AND user_id=?');$st->execute([$tok,$id,$uid]);
  flash_set($st->rowCount()>0?'success':'error',$st->rowCount()>0?'Share link generated. Use the copy button.':'Unable to generate share link.');go();
 }
 if($pa==='share_all_documents'){
  $st=db()->prepare('SELECT id FROM user_documents WHERE user_id=?');$st->execute([$uid]);$rows=$st->fetchAll()?:[];
  if(!$rows){flash_set('error','No documents available to share.');go();}
  $up=db()->prepare('UPDATE user_documents SET shared_token=?,updated_at=NOW() WHERE id=? AND user_id=?');foreach($rows as $r){$up->execute([bin2hex(random_bytes(16)),(int)$r['id'],$uid]);}
  flash_set('success','Share links generated for all documents.');go();
 }
 if($pa==='verify_document'||$pa==='reject_document'){
  if(!$isReviewer){flash_set('error','Only HR/Admin can review document status.');go();}
  $id=(int)($_POST['document_id']??0);$new=$pa==='verify_document'?'verified':'rejected';
  $st=db()->prepare('SELECT id,user_id,doc_name,status FROM user_documents WHERE id=? LIMIT 1');$st->execute([$id]);$doc=$st->fetch();
  if(!$doc){flash_set('error','Document not found.');go();}
  $old=(string)($doc['status']??'pending');
  if($old===$new){flash_set('success','Document is already marked as '.($new==='verified'?'verified':'rejected').'.');go();}
  db()->prepare('UPDATE user_documents SET status=?,updated_at=NOW() WHERE id=? LIMIT 1')->execute([$new,$id]);
  docs_log_status((int)$doc['id'],(int)$doc['user_id'],$uid,$old,$new,'Reviewed via HR/Admin action');
  create_notification((int)$doc['user_id'],'document_review','Document '.($new==='verified'?'verified':'rejected'),(string)$doc['doc_name'].' status changed to '.($new==='verified'?'Verified':'Rejected').'.','documents.php');
  flash_set('success','Document status updated to '.($new==='verified'?'Verified':'Rejected').'.');go();
 }
 flash_set('error','Unknown action requested.');go();
}

$filter=trim((string)($_GET['category']??'')); if($filter!==''&&!isset($cats[$filter]))$filter='';
$all=(($_GET['all']??'')==='1'); $lim=$all?500:10;
$params=[];$where='WHERE 1=1';
if(!$isReviewer){$where.=' AND d.user_id=?';$params[]=$uid;}
elseif($reviewUserId>0){$where.=' AND d.user_id=?';$params[]=$reviewUserId;}
if($filter!==''){ $where.=' AND d.category=?'; $params[]=$filter; }
$sql='SELECT d.*,u.first_name,u.last_name,u.email AS owner_email FROM user_documents d LEFT JOIN users u ON u.id=d.user_id '.$where.' ORDER BY d.updated_at DESC,d.created_at DESC LIMIT '.(int)$lim;
$st=db()->prepare($sql);$st->execute($params);$docs=$st->fetchAll()?:[];
$stAll=db()->prepare('SELECT d.*,u.first_name,u.last_name,u.email AS owner_email FROM user_documents d LEFT JOIN users u ON u.id=d.user_id '.$where.' ORDER BY d.updated_at DESC,d.created_at DESC');$stAll->execute($params);$allDocs=$stAll->fetchAll()?:[];
$stats=['total'=>count($allDocs),'bytes'=>0,'verified'=>0,'pending'=>0,'rejected'=>0];$catStats=[];foreach($cats as $k=>$m){$catStats[$k]=['count'=>0,'bytes'=>0,'recent'=>[]];}
$hist=[];foreach($allDocs as $d){$b=(int)$d['file_size'];$stats['bytes']+=$b; $s=(string)$d['status']; if($s==='verified')$stats['verified']++;elseif($s==='rejected')$stats['rejected']++;else $stats['pending']++; $c=(string)($d['category']??'others'); if(!isset($catStats[$c]))$catStats[$c]=['count'=>0,'bytes'=>0,'recent'=>[]]; $catStats[$c]['count']++;$catStats[$c]['bytes']+=$b; if(count($catStats[$c]['recent'])<2)$catStats[$c]['recent'][]=$d; if(count($hist)<8)$hist[]=$d; }
$reviewLogs=[];
if($isReviewer){
 $lp=[];$lw='WHERE 1=1';
 if($reviewUserId>0){$lw.=' AND l.user_id=?';$lp[]=$reviewUserId;}
 $lst=db()->prepare('SELECT l.*,d.doc_name,u.email AS owner_email,cb.email AS reviewer_email FROM user_document_status_logs l LEFT JOIN user_documents d ON d.id=l.document_id LEFT JOIN users u ON u.id=l.user_id LEFT JOIN users cb ON cb.id=l.changed_by '.$lw.' ORDER BY l.created_at DESC LIMIT 25');
 $lst->execute($lp);$reviewLogs=$lst->fetchAll()?:[];
}
$fl=flash_get();$fm=(string)($fl['m']??'');$fs=(string)($fl['s']??'');$open=isset($_GET['open_upload']);
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=$_SERVER['HTTP_HOST']??'localhost';$baseDir=rtrim(dirname($_SERVER['SCRIPT_NAME']??'/documents.php'),'/\\').'/';$base=$scheme.'://'.$host.$baseDir;

require_once 'includes/header.php';
?>
<?php if($fm!==''): ?><div class="notice notice--<?php echo $fs==='success'?'success':'danger'; ?>" style="margin-bottom:14px;"><i class="fa-solid <?php echo $fs==='success'?'fa-circle-check':'fa-triangle-exclamation'; ?>" aria-hidden="true"></i><div><?php echo htmlspecialchars($fm); ?></div></div><?php endif; ?>
<section class="hero modern-hero documents-hero">
<div class="hero-content"><div class="hero-header"><div class="header-badge header-badge--gov"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></div><div><span class="eyebrow">Document Portal</span><h3>My Documents</h3><p class="text-muted small-text">Manage your personal files</p></div></div>
<p>Upload, view, and manage your personal documents including certificates, clearances, and other required files.</p>
<div class="quick-actions">
<button class="quick-action-card quick-action-primary" type="button" data-open-upload-modal><div class="action-icon"><i class="fa-solid fa-upload" aria-hidden="true"></i></div><div class="action-content"><h4>Upload Document</h4><p>Add new files</p></div><i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i></button>
<a href="documents.php?action=download_all" class="quick-action-card quick-action-blue"><div class="action-icon"><i class="fa-solid fa-download" aria-hidden="true"></i></div><div class="action-content"><h4>Download All</h4><p>Get ZIP archive</p></div><i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i></a>
<form method="post" class="quick-action-inline-form"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><input type="hidden" name="action" value="share_all_documents"><button class="quick-action-card quick-action-success" type="submit"><div class="action-icon"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></div><div class="action-content"><h4>Share Documents</h4><p>Generate share links</p></div><i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i></button></form>
<a href="#documentHistory" class="quick-action-card quick-action-purple"><div class="action-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></div><div class="action-content"><h4>Document History</h4><p>View changes</p></div><i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i></a>
</div></div>
<div class="hero-panel modern-panel"><div class="stat-widget stat-widget--primary"><div class="stat-header stat-header--primary"><span class="stat-icon"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></span><h4>Total Documents</h4></div><div class="stat-body"><p class="stat-number stat-number--primary"><?php echo (int)$stats['total']; ?></p><p class="stat-label">Files currently stored</p></div></div><div class="stat-widget stat-widget--info"><div class="stat-header stat-header--info"><span class="stat-icon"><i class="fa-solid fa-database" aria-hidden="true"></i></span><h4>Storage Used</h4></div><div class="stat-body"><p class="stat-number stat-number--primary"><?php echo htmlspecialchars(docs_size((int)$stats['bytes'])); ?></p><p class="stat-label">Current upload footprint</p></div></div><div class="stat-widget stat-widget--success"><div class="stat-header stat-header--success"><span class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><h4>Status</h4></div><div class="stat-body"><p class="stat-number stat-number--success"><?php echo (int)$stats['verified']; ?>/<?php echo (int)$stats['total']; ?></p><p class="stat-label">Verified documents count</p></div></div></div>
</section>
<section class="activities-section"><div class="section-title"><h3><i class="fas fa-folder"></i> Document Categories</h3><p>Browse your documents by category</p></div><div class="document-categories">
<?php foreach($cats as $ck=>$cm): $cd=$catStats[$ck]??['count'=>0,'bytes'=>0,'recent'=>[]]; ?>
<div class="category-card"><div class="category-header"><i class="fas <?php echo htmlspecialchars($cm['icon']); ?>"></i><h4><?php echo htmlspecialchars($cm['label']); ?></h4></div><div class="category-content"><div class="category-stats"><span class="doc-count"><?php echo (int)$cd['count']; ?> files</span><span class="doc-size"><?php echo htmlspecialchars(docs_size((int)$cd['bytes'])); ?></span></div><div class="recent-docs"><?php if(!empty($cd['recent'])): foreach($cd['recent'] as $rd): ?><div class="doc-item"><i class="fas <?php echo htmlspecialchars(docs_icon((string)$rd['file_path'],(string)$rd['mime_type'])); ?>"></i><span><?php echo htmlspecialchars((string)($rd['doc_name']?:$rd['original_name'])); ?></span><span class="doc-status <?php echo (string)$rd['status']==='verified'?'verified':'pending'; ?>"><?php echo (string)$rd['status']==='verified'?'Verified':'Pending'; ?></span></div><?php endforeach; else: ?><div class="doc-item"><i class="fas fa-file"></i><span>No documents yet</span><span class="doc-status pending">Empty</span></div><?php endif; ?></div><a class="btn btn-primary btn-sm" href="documents.php?category=<?php echo urlencode($ck); ?>#recentDocuments">View All</a></div></div>
<?php endforeach; ?></div></section>

<section class="card recent-uploads-card" id="recentDocuments"><div class="section-head"><div><span class="tag">Recent Uploads</span><h3>Recently Uploaded Documents</h3></div><div class="section-actions"><?php if($all): ?><a class="btn btn-outline btn-sm" href="documents.php<?php echo $filter!==''?'?category='.urlencode($filter):''; ?>#recentDocuments">Show Less</a><?php else: ?><a class="btn btn-outline btn-sm" href="documents.php?all=1<?php echo $filter!==''?'&category='.urlencode($filter):''; ?>#recentDocuments">View All</a><?php endif; ?></div></div>
<div class="documents-table"><table class="data-table"><thead><tr><th>Document Name</th><?php if($isReviewer): ?><th>Owner</th><?php endif; ?><th>Category</th><th>Size</th><th>Upload Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($docs)): ?><tr><td colspan="<?php echo $isReviewer?7:6; ?>" class="text-muted">No documents found. Upload your first document.</td></tr><?php else: foreach($docs as $d): $id=(int)$d['id']; $tok=trim((string)$d['shared_token']); $share=$tok!==''?$base.'documents.php?shared='.rawurlencode($tok):''; ?>
<tr>
  <td>
    <div class="doc-name">
      <i class="fas <?php echo htmlspecialchars(docs_icon((string)$d['file_path'],(string)$d['mime_type'])); ?>"></i>
      <span><?php echo htmlspecialchars((string)($d['doc_name']?:$d['original_name'])); ?></span>
    </div>
    <?php if($share!==''): ?>
      <div class="share-link-row">
        <code><?php echo htmlspecialchars($share); ?></code>
        <button type="button" class="action-btn action-btn-copy copy-share-btn" data-link="<?php echo htmlspecialchars($share); ?>" title="Copy share link" aria-label="Copy share link">
          <img class="action-table-icon" src="https://img.icons8.com/fluency-systems-filled/18/000000/copy.png" alt="" aria-hidden="true">
        </button>
      </div>
    <?php endif; ?>
  </td>
  <?php if($isReviewer): ?><td><?php echo htmlspecialchars(trim(((string)($d['first_name']??'')).' '.((string)($d['last_name']??'')))?:((string)($d['owner_email']??'Unknown'))); ?></td><?php endif; ?>
  <td><?php echo htmlspecialchars(docs_label($cats,(string)$d['category'])); ?></td>
  <td><?php echo htmlspecialchars(docs_size((int)$d['file_size'])); ?></td>
  <td><?php echo htmlspecialchars(date('M d, Y',strtotime((string)$d['created_at']))); ?></td>
  <td>
    <?php
      $rowStatus = strtolower((string)($d['status'] ?? 'pending'));
      $rowStatusClass = in_array($rowStatus, ['verified', 'pending', 'rejected'], true) ? $rowStatus : 'pending';
      $rowStatusLabel = $rowStatus === 'verified' ? 'Verified' : ($rowStatus === 'rejected' ? 'Rejected' : 'Pending');
    ?>
    <span class="status-badge <?php echo htmlspecialchars($rowStatusClass); ?>">
      <?php echo htmlspecialchars($rowStatusLabel); ?>
    </span>
  </td>
  <td>
    <div class="table-actions">
      <div class="action-menu-wrap">
        <button type="button" class="action-menu-trigger" aria-haspopup="true" aria-expanded="false" title="Open actions" aria-label="Open actions">
          <i class="fa-solid fa-ellipsis"></i>
        </button>
        <div class="action-menu" role="menu">
          <a href="documents.php?action=view&id=<?php echo $id; ?>" class="action-menu-item" role="menuitem" target="_blank" rel="noopener">
            <i class="fa-solid fa-eye"></i><span>View</span>
          </a>
          <a href="documents.php?action=download&id=<?php echo $id; ?>" class="action-menu-item" role="menuitem">
            <i class="fa-solid fa-download"></i><span>Download</span>
          </a>
          <form method="post" class="action-menu-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="share_document">
            <input type="hidden" name="document_id" value="<?php echo $id; ?>">
            <button type="submit" class="action-menu-item" role="menuitem">
              <i class="fa-solid fa-share-nodes"></i><span>Share</span>
            </button>
          </form>
          <button type="button" class="action-menu-item action-menu-item-edit" role="menuitem"
            data-open-upload-modal
            data-mode="update"
            data-id="<?php echo $id; ?>"
            data-category="<?php echo htmlspecialchars((string)$d['category']); ?>"
            data-name="<?php echo htmlspecialchars((string)$d['doc_name']); ?>"
            data-description="<?php echo htmlspecialchars((string)$d['description']); ?>">
            <i class="fa-solid fa-pen-to-square"></i><span>Edit</span>
          </button>
          <?php if($isReviewer): ?>
          <form method="post" class="action-menu-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="verify_document">
            <input type="hidden" name="document_id" value="<?php echo $id; ?>">
            <button type="submit" class="action-menu-item action-menu-item-verify" role="menuitem">
              <i class="fa-solid fa-circle-check"></i><span>Verify</span>
            </button>
          </form>
          <form method="post" class="action-menu-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="reject_document">
            <input type="hidden" name="document_id" value="<?php echo $id; ?>">
            <button type="submit" class="action-menu-item action-menu-item-reject" role="menuitem">
              <i class="fa-solid fa-circle-xmark"></i><span>Reject</span>
            </button>
          </form>
          <?php endif; ?>
          <form method="post" class="action-menu-form" onsubmit="return confirm('Delete this document permanently?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="delete_document">
            <input type="hidden" name="document_id" value="<?php echo $id; ?>">
            <button type="submit" class="action-menu-item action-menu-item-delete" role="menuitem">
              <i class="fa-regular fa-trash-can"></i><span>Delete</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></section>

<section class="card" id="documentHistory"><div class="section-head"><div><span class="tag">Timeline</span><h3>Document History</h3></div></div><div class="recent-docs"><?php if(empty($hist)): ?><div class="doc-item"><i class="fas fa-clock"></i><span>No history yet.</span></div><?php else: foreach($hist as $h): ?><div class="doc-item"><i class="fas <?php echo htmlspecialchars(docs_icon((string)$h['file_path'],(string)$h['mime_type'])); ?>"></i><span><?php echo htmlspecialchars((string)($h['doc_name']?:$h['original_name'])); ?></span><span class="doc-status <?php echo (string)$h['status']==='verified'?'verified':((string)$h['status']==='rejected'?'rejected':'pending'); ?>"><?php echo htmlspecialchars(date('M d, Y h:i A',strtotime((string)($h['updated_at']?:$h['created_at'])))); ?></span></div><?php endforeach; endif; ?></div></section>
<?php if($isReviewer): ?>
<section class="card"><div class="section-head"><div><span class="tag">Audit Trail</span><h3>Status Review Log</h3></div></div><div class="documents-table"><table class="data-table"><thead><tr><th>Document</th><th>Owner</th><th>Changed By</th><th>From</th><th>To</th><th>Date</th></tr></thead><tbody><?php if(empty($reviewLogs)): ?><tr><td colspan="6" class="text-muted">No review actions yet.</td></tr><?php else: foreach($reviewLogs as $lg): ?><tr><td><?php echo htmlspecialchars((string)($lg['doc_name']?:('Document #'.(int)$lg['document_id']))); ?></td><td><?php echo htmlspecialchars((string)($lg['owner_email']??'')); ?></td><td><?php echo htmlspecialchars((string)($lg['reviewer_email']??'')); ?></td><td><span class="status-badge <?php echo htmlspecialchars((string)$lg['old_status']==='rejected'?'rejected':((string)$lg['old_status']==='verified'?'verified':'pending')); ?>"><?php echo htmlspecialchars(ucfirst((string)$lg['old_status'])); ?></span></td><td><span class="status-badge <?php echo htmlspecialchars((string)$lg['new_status']==='rejected'?'rejected':((string)$lg['new_status']==='verified'?'verified':'pending')); ?>"><?php echo htmlspecialchars(ucfirst((string)$lg['new_status'])); ?></span></td><td><?php echo htmlspecialchars(date('M d, Y h:i A',strtotime((string)$lg['created_at']))); ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
<?php endif; ?>

<div id="uploadModal" class="modal<?php echo $open?' is-open':''; ?>" aria-hidden="<?php echo $open?'false':'true'; ?>"><div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="uploadModalTitle"><div class="modal-header"><h3 id="uploadModalTitle">Upload Document</h3><button class="modal-close" type="button" data-close-upload-modal aria-label="Close">&times;</button></div><div class="modal-body"><form class="upload-form" method="POST" enctype="multipart/form-data" id="uploadForm"><input type="hidden" name="action" id="uploadFormAction" value="upload_document"><input type="hidden" name="document_id" id="uploadDocumentId" value="0"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><div class="form-group"><label for="docCategory">Document Category</label><select id="docCategory" name="docCategory" required><option value="">Select category</option><?php foreach($cats as $k=>$m): ?><option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($m['label']); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="docName">Document Name</label><input type="text" id="docName" name="docName" placeholder="Enter document name" required></div><div class="form-group"><label for="docFile">Choose File</label><input type="file" id="docFile" name="docFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required><small>Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small></div><div class="form-group"><label for="docDescription">Description</label><textarea id="docDescription" name="docDescription" placeholder="Brief description of the document"></textarea></div><div class="form-actions"><button type="submit" class="btn btn-primary" id="uploadBtn">Upload Document</button><button type="button" class="btn btn-outline" data-close-upload-modal>Cancel</button></div></form></div></div></div>

<style>
.quick-action-inline-form{margin:0}
.quick-action-inline-form .quick-action-card{width:100%}
.documents-hero .hero-content{animation:fadeInUp .6s ease-out both}
.documents-hero .quick-actions .quick-action-card{animation:fadeInUp .45s ease-out both}
.documents-hero .quick-actions > :nth-child(1) .quick-action-card,
.documents-hero .quick-actions > :nth-child(1).quick-action-card{animation-delay:.04s}
.documents-hero .quick-actions > :nth-child(2) .quick-action-card,
.documents-hero .quick-actions > :nth-child(2).quick-action-card{animation-delay:.1s}
.documents-hero .quick-actions > :nth-child(3) .quick-action-card,
.documents-hero .quick-actions > :nth-child(3).quick-action-card{animation-delay:.16s}
.documents-hero .quick-actions > :nth-child(4) .quick-action-card,
.documents-hero .quick-actions > :nth-child(4).quick-action-card{animation-delay:.22s}
.documents-hero .modern-panel .stat-widget{animation:fadeInUp .5s ease-out both}
.documents-hero .modern-panel .stat-widget:nth-child(1){animation-delay:.08s}
.documents-hero .modern-panel .stat-widget:nth-child(2){animation-delay:.16s}
.documents-hero .modern-panel .stat-widget:nth-child(3){animation-delay:.24s}

.document-categories{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:16px;
  margin-top:16px;
}
.category-card{
  border:1px solid #dce8f5;
  border-radius:14px;
  background:#fff;
  overflow:hidden;
  box-shadow:0 10px 24px rgba(17,43,70,.08);
}
.category-header{
  display:flex;
  align-items:center;
  gap:12px;
  padding:16px 18px;
  color:#fff;
  background:linear-gradient(135deg,#1f4d85,#2c6cb0);
}
.category-header h4{margin:0;font-size:16px}
.category-header i{font-size:18px}
.category-content{padding:14px 16px}
.category-stats{
  display:flex;
  justify-content:space-between;
  color:#617993;
  font-size:13px;
  margin-bottom:10px;
}
.recent-docs{margin:0 0 12px}
.doc-item{
  display:flex;
  align-items:center;
  gap:8px;
  padding:8px 0;
  border-bottom:1px solid #edf3fb;
}
.doc-item:last-child{border-bottom:0}
.doc-item i{color:#2f7dbf;width:18px}
.doc-status{
  margin-left:auto;
  font-size:10px;
  font-weight:700;
  letter-spacing:.04em;
  border-radius:999px;
  padding:3px 8px;
}
.doc-status.verified{background:#def5ed;color:#167b5c}
.doc-status.pending{background:#fff3dd;color:#9a6213}
.doc-status.rejected{background:#ffe5e5;color:#c23434}
.status-badge{
  display:inline-flex;
  align-items:center;
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  line-height:1;
}
.status-badge.pending{background:#fff3dd;color:#9a6213}
.status-badge.rejected{background:#ffe5e5;color:#c23434}
.status-badge.verified{background:#def5ed;color:#167b5c}

.documents-table{
  overflow:visible;
  border:1px solid #dce8f5;
  border-radius:18px;
  box-shadow:0 12px 28px rgba(17,43,70,.08);
  background:#fff;
  position:relative;
  z-index:1;
}
.documents-table .data-table{width:100%;min-width:860px}
.documents-table .data-table th{
  background:linear-gradient(180deg,#f6faff 0%,#eef4fc 100%);
  color:#21476e;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.06em;
  padding:16px 14px;
  border-bottom:1px solid #dbe7f5;
}
.documents-table .data-table td{
  vertical-align:top;
  padding:18px 14px;
  border-bottom:1px solid #edf3fb;
}
.documents-table .data-table tbody tr:last-child td{border-bottom:0}
.doc-name{display:flex;align-items:center;gap:8px}
.doc-name i{color:#1f4d85}
.table-actions{
  display:flex;
  justify-content:flex-start;
  align-items:center;
}
.action-menu-wrap{position:relative}
.action-menu-trigger{
  width:46px;
  height:36px;
  border:1px solid #cfdcf0;
  border-radius:10px;
  background:linear-gradient(180deg,#f6faff,#edf3fc);
  color:#4f6784;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .15s ease;
}
.action-menu-trigger:hover{
  border-color:#b9cdea;
  box-shadow:0 6px 16px rgba(15,76,129,.12);
}
.action-menu-wrap.is-open .action-menu-trigger{
  border-color:#b7cbe8;
  background:linear-gradient(180deg,#eef5ff,#e5eefc);
}
.action-menu{
  position:absolute;
  top:42px;
  right:0;
  min-width:160px;
  background:#fff;
  border:1px solid #d8e3f2;
  border-radius:10px;
  padding:6px;
  box-shadow:0 18px 34px rgba(20,45,78,.18);
  z-index:9999;
  display:none;
}
.action-menu-wrap.is-open .action-menu{display:block}
.action-menu-form{margin:0}
.action-menu-item{
  width:100%;
  border:0;
  background:transparent;
  color:#1e3150;
  text-decoration:none;
  border-radius:8px;
  padding:8px 9px;
  display:flex;
  align-items:center;
  gap:8px;
  font-weight:700;
  font-size:13px;
  line-height:1;
  text-align:left;
  cursor:pointer;
}
.action-menu-item + .action-menu-item,
.action-menu-form + .action-menu-form,
.action-menu-form + .action-menu-item,
.action-menu-item + .action-menu-form{
  margin-top:4px;
}
.action-menu-item i{width:16px;text-align:center;font-size:16px}
.action-menu-item span{font-size:13px;line-height:1.15;font-weight:700}
.action-menu-item:hover{background:#f1f4fa}
.action-menu-item-verify{color:#167b5c}
.action-menu-item-reject{color:#c23434}
.action-menu-item-delete{color:#d95a5a}
.action-btn{
  width:30px;
  min-width:30px;
  height:30px;
  min-height:30px;
  padding:0;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border:1px solid #d8e4f2;
  border-radius:8px;
  background:linear-gradient(135deg,#f8fbff,#eef4fc);
  box-shadow:0 2px 8px rgba(15,76,129,.08);
  cursor:pointer;
  opacity:1;
  transition:transform 120ms ease,box-shadow 120ms ease,border-color 120ms ease;
}
.action-btn:hover{
  transform:translateY(-1px);
  box-shadow:0 6px 12px rgba(15,76,129,.14);
  border-color:#c3d6ed;
}
.action-btn:focus-visible{
  outline:2px solid rgba(15,76,129,.28);
  outline-offset:2px;
  border-radius:8px;
}
.action-table-icon{
  width:16px;
  height:16px;
  display:block;
  object-fit:contain;
}
.action-btn-copy,
.action-btn-view,
.action-btn-download,
.action-btn-share,
.action-btn-update{
  background:linear-gradient(135deg,#f8fbff,#edf4ff);
}
.action-btn-delete{
  border-color:#f1c8c8;
  background:linear-gradient(135deg,#fff6f6,#ffeceb);
}
.documents-hero .quick-action-primary .action-icon i,
.documents-hero .quick-action-blue .action-icon i,
.documents-hero .quick-action-success .action-icon i,
.documents-hero .quick-action-purple .action-icon i{
  color:#fff !important;
}
.inline-form{display:inline;margin:0}

.share-link-row{
  margin-top:6px;
  display:flex;
  gap:6px;
  align-items:center;
}
.share-link-row code{
  font-size:11px;
  color:#4c6581;
  background:#edf4fb;
  border:1px solid #d8e5f2;
  border-radius:6px;
  padding:3px 6px;
  max-width:360px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.share-link-row .copy-share-btn{flex:0 0 auto}
.share-link-row .copy-share-btn.is-copied{
  opacity:1;
  transform:translateY(0);
}

.recent-uploads-card{
  border-radius:20px;
  border:1px solid #d8e4f2;
  box-shadow:0 14px 32px rgba(17,43,70,.08);
}
.recent-uploads-card .section-head{
  margin-bottom:16px;
}
.recent-uploads-card .tag{
  background:linear-gradient(135deg,#eaf3ff,#deecff);
  color:#0f4d8d;
  border:1px solid #cddff5;
}
.recent-uploads-card h3{
  letter-spacing:-.02em;
  color:#112c4b;
}

.modal{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.52);
  display:none;
  align-items:center;
  justify-content:center;
  z-index:1000;
  padding:18px;
}
.modal.is-open{display:flex}
.modal-content{
  background:#fff;
  border-radius:14px;
  width:min(100%,620px);
  max-height:calc(100dvh - 36px);
  overflow-y:auto;
  border:1px solid #d9e4f1;
  box-shadow:0 24px 40px rgba(17,43,70,.2);
}
.modal-header{
  padding:20px;
  border-bottom:1px solid #e3edf7;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.modal-header h3{margin:0}
.modal-body{padding:20px}
.modal-close{
  background:none;
  border:none;
  font-size:24px;
  cursor:pointer;
  color:#617993;
}
.upload-form .form-group{margin-bottom:18px}
.upload-form label{
  display:block;
  margin-bottom:8px;
  font-weight:700;
  color:#14324f;
  letter-spacing:.04em;
  text-transform:uppercase;
  font-size:12px;
}
.upload-form input,.upload-form select,.upload-form textarea{
  width:100%;
  padding:12px;
  border:1px solid #c9d9ea;
  border-radius:8px;
  font-size:14px;
}
.upload-form textarea{min-height:92px;resize:vertical}
.upload-form small{display:block;margin-top:5px;color:#617993;font-size:12px}

@media (max-width:768px){
  .share-link-row{flex-direction:column;align-items:flex-start}
  .share-link-row code{max-width:100%}
  .documents-table{overflow-x:auto;overflow-y:visible}
  .documents-table .data-table{min-width:760px}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('uploadForm');
  const submitBtn = document.getElementById('uploadBtn');
  const modal = document.getElementById('uploadModal');
  const formAction = document.getElementById('uploadFormAction');
  const documentId = document.getElementById('uploadDocumentId');
  const modalTitle = document.getElementById('uploadModalTitle');

  const closeMenus = () => {
    document.querySelectorAll('.action-menu-wrap.is-open').forEach((x) => {
      x.classList.remove('is-open');
      const trigger = x.querySelector('.action-menu-trigger');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  };

  const openUpload = (mode, payload) => {
    if (!modal || !form || !submitBtn || !formAction || !documentId || !modalTitle) return;
    if (mode === 'update' && payload) {
      modalTitle.textContent = 'Update Document';
      formAction.value = 'update_document';
      documentId.value = payload.id || '0';
      form.docCategory.value = payload.category || '';
      form.docName.value = payload.name || '';
      form.docDescription.value = payload.description || '';
      submitBtn.textContent = 'Save Changes';
    } else {
      modalTitle.textContent = 'Upload Document';
      formAction.value = 'upload_document';
      documentId.value = '0';
      form.reset();
      submitBtn.textContent = 'Upload Document';
    }
    submitBtn.disabled = false;
    closeMenus();
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  const closeUpload = () => {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  };

  document.querySelectorAll('[data-open-upload-modal]').forEach((el) => {
    el.addEventListener('click', () => openUpload(el.getAttribute('data-mode') || 'upload', {
      id: el.getAttribute('data-id') || '0',
      category: el.getAttribute('data-category') || '',
      name: el.getAttribute('data-name') || '',
      description: el.getAttribute('data-description') || '',
    }));
  });

  document.querySelectorAll('[data-close-upload-modal]').forEach((el) => {
    el.addEventListener('click', closeUpload);
  });

  document.querySelectorAll('.action-menu-trigger').forEach((trigger) => {
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const wrap = trigger.closest('.action-menu-wrap');
      if (!wrap) return;
      const willOpen = !wrap.classList.contains('is-open');
      closeMenus();
      wrap.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.action-menu-wrap')) closeMenus();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMenus();
      if (modal && modal.classList.contains('is-open')) closeUpload();
    }
  });

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeUpload();
    });
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      if (submitBtn.disabled) {
        e.preventDefault();
        return;
      }
      submitBtn.disabled = true;
      submitBtn.textContent = formAction.value === 'update_document' ? 'Saving...' : 'Uploading...';
    });
  }

  document.querySelectorAll('.copy-share-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const link = btn.getAttribute('data-link') || '';
      if (!link) return;
      try {
        await navigator.clipboard.writeText(link);
        btn.title = 'Copied';
        btn.setAttribute('aria-label', 'Share link copied');
        btn.classList.add('is-copied');
        setTimeout(() => {
          btn.title = 'Copy share link';
          btn.setAttribute('aria-label', 'Copy share link');
          btn.classList.remove('is-copied');
        }, 1400);
      } catch (_) {
        btn.title = 'Copy failed';
        setTimeout(() => {
          btn.title = 'Copy share link';
        }, 1400);
      }
    });
  });

  <?php if($open): ?>
  openUpload('upload');
  <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
