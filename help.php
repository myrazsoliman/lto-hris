<?php
$pageTitle = 'Help & Support';
$activePage = 'help.php';
require_once 'includes/auth.php';
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);
require_once 'includes/header.php';
require_once 'includes/data.php';

$currentUser = current_user();
$userName = $currentUser['display_name'] ?? 'Employee';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-question-circle" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">Help & Support</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Get assistance with HR-related concerns</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Find answers to common questions, access helpful resources, and contact HR support for assistance with your employment-related needs and system navigation.
        </p>

        <div class="quick-actions">
            <a href="#faq" class="quick-action-card quick-action-purple">
                <div class="action-icon" style="background: #9b59b6; color: white;"><i class="fas fa-question"></i></div>
                <div class="action-content">
                    <h4>FAQ</h4>
                    <p>Frequently asked questions</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="#guides" class="quick-action-card quick-action-blue">
                <div class="action-icon" style="background: #2196f3; color: white;"><i class="fas fa-book"></i></div>
                <div class="action-content">
                    <h4>User Guides</h4>
                    <p>Step-by-step tutorials</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="#contact" class="quick-action-card quick-action-green">
                <div class="action-icon" style="background: #27ae60; color: white;"><i class="fas fa-envelope"></i></div>
                <div class="action-content">
                    <h4>Contact HR</h4>
                    <p>Send support request</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="#policies" class="quick-action-card quick-action-orange">
                <div class="action-icon" style="background: #f39c12; color: white;"><i class="fas fa-gavel"></i></div>
                <div class="action-content">
                    <h4>Policies</h4>
                    <p>HR policies & procedures</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget" style="border-top: 4px solid #9b59b6;">
            <div class="stat-header" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <span class="stat-icon"><i class="fas fa-book-open"></i></span>
                <h4>Knowledge Base</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #9b59b6;">45</p>
                <p class="stat-label">Help articles available</p>
                <div style="margin-top: 12px; background: #f3e5f5; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #9b59b6, #8e44ad); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #27ae60;">
            <div class="stat-header" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <span class="stat-icon"><i class="fas fa-headset"></i></span>
                <h4>Support Team</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #27ae60;">Online</p>
                <p class="stat-label">HR staff available</p>
                <div style="margin-top: 12px; padding: 8px 12px; background: #e8f5e9; border-radius: 6px; border-left: 3px solid #27ae60; font-size: 12px; color: #229954; font-weight: 500;">
                    Response time: < 2 hours
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #2196f3;">
            <div class="stat-header" style="background: linear-gradient(135deg, #2196f3, #1976d2);">
                <span class="stat-icon"><i class="fas fa-ticket-alt"></i></span>
                <h4>Open Tickets</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #2196f3;">3</p>
                <p class="stat-label">Your support requests</p>
                <div style="margin-top: 12px; background: #e3f2fd; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 60%; height: 100%; background: linear-gradient(90deg, #2196f3, #00bcd4); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #f39c12;">
            <div class="stat-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <span class="stat-icon"><i class="fas fa-phone"></i></span>
                <h4>Hotline</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #f39c12;">24/7</p>
                <p class="stat-label">Emergency support</p>
                <div style="margin-top: 12px; display: flex; gap: 6px;">
                    <span style="background: #fef5e7; color: #e67e22; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">Available</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section id="faq" class="card">
    <div class="section-head">
        <div>
            <span class="tag">FAQ</span>
            <h3>Frequently Asked Questions</h3>
        </div>
    </div>

    <div class="faq-container">
        <div class="faq-category">
            <h4><i class="fas fa-user"></i> Account & Login</h4>
            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I reset my password?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>To reset your password, click on the "Forgot Password" link on the login page. Enter your email address and follow the instructions sent to your email. If you don't receive the email, check your spam folder or contact HR support.</p>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <span>Why can't I access my account?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Common reasons include: incorrect password, locked account due to multiple failed attempts, or account deactivation. Try resetting your password first. If that doesn't work, contact HR support for assistance.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h4><i class="fas fa-file-alt"></i> Documents & Forms</h4>
            <div class="faq-item">
                <div class="faq-question">
                    <span>What documents do I need to upload?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Required documents include: valid ID, birth certificate, educational diplomas/transcripts, medical certificates, and training certificates. Check your profile for any missing documents marked as "Pending".</p>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I update my PDS?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Navigate to "My PDS" from the dashboard. Click "Edit PDS" and update the required fields. Make sure to save your changes and upload any supporting documents. HR will review and verify your updated information.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h4><i class="fas fa-calendar-alt"></i> Leave & Time Off</h4>
            <div class="faq-item">
                <div class="faq-question">
                    <span>How many leave days do I have?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Your leave balance is displayed on your dashboard. Regular employees typically have 15 vacation days and 10 sick leave days per year. Leave balances reset on January 1st each year.</p>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <span>How far in advance should I apply for leave?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Vacation leave should be applied at least 5 working days in advance. Sick leave can be applied on the day of illness. Emergency leave may be approved retroactively with proper justification.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- USER GUIDES -->
<section id="guides" class="card">
    <div class="section-head">
        <div>
            <span class="tag">Guides</span>
            <h3>User Guides & Tutorials</h3>
        </div>
    </div>

    <div class="guides-grid">
        <div class="guide-card">
            <div class="guide-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-play-circle"></i>
            </div>
            <h4>Getting Started</h4>
            <p>Learn the basics of navigating the HRIS system</p>
            <div class="guide-meta">
                <span class="guide-duration">5 min read</span>
                <span class="guide-type">Video Tutorial</span>
            </div>
            <button class="btn btn-primary btn-sm">Watch Now</button>
        </div>

        <div class="guide-card">
            <div class="guide-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <i class="fas fa-file-upload"></i>
            </div>
            <h4>Uploading Documents</h4>
            <p>Step-by-step guide to upload and manage your documents</p>
            <div class="guide-meta">
                <span class="guide-duration">3 min read</span>
                <span class="guide-type">Article</span>
            </div>
            <button class="btn btn-primary btn-sm">Read Guide</button>
        </div>

        <div class="guide-card">
            <div class="guide-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h4>Leave Application</h4>
            <p>How to apply for different types of leave</p>
            <div class="guide-meta">
                <span class="guide-duration">4 min read</span>
                <span class="guide-type">Interactive Guide</span>
            </div>
            <button class="btn btn-primary btn-sm">Start Guide</button>
        </div>

        <div class="guide-card">
            <div class="guide-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <i class="fas fa-balance-scale"></i>
            </div>
            <h4>SALN Filing</h4>
            <p>Complete guide to filing your Statement of Assets and Liabilities</p>
            <div class="guide-meta">
                <span class="guide-duration">8 min read</span>
                <span class="guide-type">PDF Guide</span>
            </div>
            <button class="btn btn-primary btn-sm">Download</button>
        </div>
    </div>
</section>

<!-- CONTACT SUPPORT -->
<section id="contact" class="card">
    <div class="section-head">
        <div>
            <span class="tag">Contact</span>
            <h3>Contact HR Support</h3>
        </div>
    </div>

    <div class="contact-container">
        <div class="contact-form-section">
            <h4>Submit a Support Request</h4>
            <form class="support-form">
                <div class="form-group">
                    <label for="issue_type">Issue Type</label>
                    <select id="issue_type" required>
                        <option value="">Select issue type</option>
                        <option value="login">Login/Account Issue</option>
                        <option value="document">Document Problem</option>
                        <option value="leave">Leave Request Issue</option>
                        <option value="pds">PDS/SALN Problem</option>
                        <option value="technical">Technical Issue</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" placeholder="Brief description of your issue" required>
                </div>
                <div class="form-group">
                    <label for="description">Detailed Description</label>
                    <textarea id="description" rows="5" placeholder="Please provide detailed information about your issue..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" required>
                        <option value="low">Low - General inquiry</option>
                        <option value="medium">Medium - Need assistance</option>
                        <option value="high">High - Urgent issue</option>
                        <option value="critical">Critical - System down</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                    <button type="button" class="btn btn-outline">Save as Draft</button>
                </div>
            </form>
        </div>

        <div class="contact-info-section">
            <div class="contact-method">
                <div class="contact-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="contact-details">
                    <h4>Phone Support</h4>
                    <p><strong>Hotline:</strong> (049) 576-1234</p>
                    <p><strong>Hours:</strong> Monday-Friday, 8:00 AM - 5:00 PM</p>
                    <p><strong>Emergency:</strong> 0912-345-6789 (24/7)</p>
                </div>
            </div>

            <div class="contact-method">
                <div class="contact-icon" style="background: linear-gradient(135deg, #2196f3, #1976d2);">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-details">
                    <h4>Email Support</h4>
                    <p><strong>General:</strong> hr@lto-pila.gov.ph</p>
                    <p><strong>Technical:</strong> support@lto-pila.gov.ph</p>
                    <p><strong>Response Time:</strong> Within 24 hours</p>
                </div>
            </div>

            <div class="contact-method">
                <div class="contact-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="contact-details">
                    <h4>Office Visit</h4>
                    <p><strong>HR Office:</strong> LTO Pila, Laguna</p>
                    <p><strong>Address:</strong> National Highway, Pila, Laguna</p>
                    <p><strong>Walk-in Hours:</strong> 9:00 AM - 4:00 PM</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HR POLICIES -->
<section id="policies" class="card">
    <div class="section-head">
        <div>
            <span class="tag">Policies</span>
            <h3>HR Policies & Procedures</h3>
        </div>
    </div>

    <div class="policies-grid">
        <div class="policy-item">
            <div class="policy-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-book"></i>
            </div>
            <div class="policy-content">
                <h4>Employee Handbook</h4>
                <p>Comprehensive guide to company policies, procedures, and employee conduct</p>
                <div class="policy-meta">
                    <span class="policy-date">Updated: March 2025</span>
                    <span class="policy-type">PDF</span>
                </div>
                <button class="btn btn-outline btn-sm">Download</button>
            </div>
        </div>

        <div class="policy-item">
            <div class="policy-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-gavel"></i>
            </div>
            <div class="policy-content">
                <h4>Code of Conduct</h4>
                <p>Ethical guidelines and professional standards for all employees</p>
                <div class="policy-meta">
                    <span class="policy-date">Updated: January 2025</span>
                    <span class="policy-type">PDF</span>
                </div>
                <button class="btn btn-outline btn-sm">Download</button>
            </div>
        </div>

        <div class="policy-item">
            <div class="policy-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="policy-content">
                <h4>Leave Policy</h4>
                <p>Detailed guidelines for leave types, application procedures, and approval process</p>
                <div class="policy-meta">
                    <span class="policy-date">Updated: February 2025</span>
                    <span class="policy-type">PDF</span>
                </div>
                <button class="btn btn-outline btn-sm">Download</button>
            </div>
        </div>

        <div class="policy-item">
            <div class="policy-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="policy-content">
                <h4>Data Privacy Policy</h4>
                <p>How we collect, use, and protect your personal information</p>
                <div class="policy-meta">
                    <span class="policy-date">Updated: December 2024</span>
                    <span class="policy-type">PDF</span>
                </div>
                <button class="btn btn-outline btn-sm">Download</button>
            </div>
        </div>
    </div>
</section>

<style>
.faq-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.faq-category {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
}

.faq-category h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.faq-item {
    margin-bottom: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.faq-question {
    padding: 15px;
    background: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #2c3e50;
}

.faq-question:hover {
    background: #f8f9fa;
}

.faq-answer {
    padding: 15px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: none;
}

.faq-item.active .faq-answer {
    display: block;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.guides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.guide-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
}

.guide-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px auto;
    color: white;
    font-size: 24px;
}

.guide-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.guide-card p {
    color: #7f8c8d;
    margin-bottom: 15px;
}

.guide-meta {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 20px;
    font-size: 12px;
    color: #95a5a6;
}

.contact-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-top: 20px;
}

.support-form .form-group {
    margin-bottom: 20px;
}

.support-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.support-form input,
.support-form select,
.support-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.contact-method {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.contact-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    flex-shrink: 0;
}

.contact-details h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.contact-details p {
    margin: 5px 0;
    font-size: 14px;
    color: #7f8c8d;
}

.policies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.policy-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.policy-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    flex-shrink: 0;
}

.policy-content h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.policy-content p {
    color: #7f8c8d;
    margin-bottom: 10px;
    font-size: 14px;
}

.policy-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 12px;
    color: #95a5a6;
}

@media (max-width: 768px) {
    .contact-container {
        grid-template-columns: 1fr;
    }
    
    .policy-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Accordion
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
