<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Appointment Letter - {{ $reference_number ?? 'Blueboxx' }}</title>
<style>
  @page {
    size: a4 portrait;
    margin: 0;
  }
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
  }
  body {
    font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
    font-size: 11.2px;
    line-height: 1.55;
    color: #1a202c;
    background: #ffffff;
    width: 210mm;
    margin: 0;
    padding: 0;
  }

  .page {
    position: relative;
    width: 210mm;
    height: 297mm;
    box-sizing: border-box;
    padding: 30px 48px 75px 48px;
    page-break-after: always;
    overflow: hidden;
  }

  .page:last-child {
    page-break-after: auto;
  }

  /* Top Right Decorative Corner */
  .corner-tr {
    position: absolute;
    top: 0;
    right: 0;
    width: 140px;
    height: 140px;
    z-index: 1;
  }
  .corner-tr-orange {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 130px 130px 0;
    border-color: transparent #f26522 transparent transparent;
  }
  .corner-tr-navy {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 95px 95px 0;
    border-color: transparent #0d1635 transparent transparent;
  }

  /* Bottom Left Decorative Corner */
  .corner-bl {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 140px;
    height: 140px;
    z-index: 1;
  }
  .corner-bl-navy {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 130px 0 0 130px;
    border-color: transparent transparent transparent #0d1635;
  }
  .corner-bl-orange {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 95px 0 0 95px;
    border-color: transparent transparent transparent #f26522;
  }

  /* Header area */
  .header-table {
    width: 100%;
    margin-bottom: 22px;
    position: relative;
    z-index: 5;
  }
  .logo-title {
    font-size: 26px;
    font-weight: 900;
    color: #1B2A6B;
    letter-spacing: 0.5px;
    line-height: 1.1;
  }
  .logo-subtitle {
    font-size: 8px;
    font-weight: 700;
    color: #4a5568;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    margin-top: 3px;
  }

  /* Document Title */
  .doc-title {
    text-align: center;
    font-size: 22px;
    font-weight: 900;
    color: #0d1635;
    letter-spacing: 0.5px;
    margin: 10px 0 20px 0;
  }

  /* Meta info row */
  .meta-table {
    width: 100%;
    margin-bottom: 12px;
    font-size: 11.5px;
  }
  .salutation {
    font-weight: bold;
    color: #0d1635;
  }
  .meta-date {
    text-align: right;
    font-weight: bold;
    color: #0d1635;
  }

  p {
    font-size: 11px;
    line-height: 1.52;
    color: #2d3748;
    margin-bottom: 10px;
    text-align: justify;
  }

  .section-title {
    font-size: 12.5px;
    font-weight: bold;
    color: #0d1635;
    margin-top: 13px;
    margin-bottom: 7px;
  }

  /* Details list */
  .bullet-list {
    list-style-type: none;
    margin: 0 0 10px 0;
    padding: 0;
  }
  .bullet-list li {
    font-size: 11px;
    line-height: 1.5;
    color: #2d3748;
    margin-bottom: 4px;
    padding-left: 14px;
    position: relative;
  }
  .bullet-list li::before {
    content: "•";
    position: absolute;
    left: 0;
    color: #1B2A6B;
    font-weight: bold;
  }

  /* Bottom Yellow Bar */
  .footer-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #f7b928;
    padding: 7px 20px 7px 70px;
    text-align: center;
    font-size: 9.5px;
    font-weight: 600;
    color: #1a202c;
    z-index: 10;
    box-sizing: border-box;
  }
  .footer-bar-contact {
    margin-bottom: 2px;
    font-size: 10px;
    font-weight: bold;
    color: #0d1635;
  }
  .footer-bar-address {
    font-size: 9px;
    color: #1a202c;
  }

  /* Signature section */
  .signature-container {
    margin-top: 20px;
    width: 320px;
  }
  .sig-img-box {
    min-height: 50px;
    max-height: 60px;
    margin: 4px 0;
  }
  .sig-img {
    max-height: 52px;
    max-width: 180px;
    display: block;
  }
  .sig-line-blank {
    display: inline-block;
    width: 200px;
    border-bottom: 1.2px solid #2d3748;
    margin-bottom: 3px;
  }
  .sig-label {
    font-size: 11.5px;
    font-weight: bold;
    color: #0d1635;
    margin-top: 4px;
  }
</style>
</head>
<body>

<!-- ================= PAGE 1 ================= -->
<div class="page">
  <!-- Top Right Accent -->
  <div class="corner-tr">
    <div class="corner-tr-orange"></div>
    <div class="corner-tr-navy"></div>
  </div>

  <!-- Header / Logo -->
  <table class="header-table" cellpadding="0" cellspacing="0">
    <tr>
      <td style="vertical-align: middle; width: 60px;">
        @if(!empty($logo_base64))
          <img src="{{ $logo_base64 }}" style="max-height: 48px; max-width: 50px;" alt="Logo" />
        @else
          <div style="width: 44px; height: 44px; background: #1B2A6B; border-radius: 8px; text-align: center; line-height: 44px; color: #fff; font-weight: bold; font-size: 18px;">BB</div>
        @endif
      </td>
      <td style="vertical-align: middle; padding-left: 10px;">
        <div class="logo-title">BLUEBOXX DA</div>
        <div class="logo-subtitle">ADVERTISING | TRAINING | PLACEMENT | ANIMATION</div>
      </td>
    </tr>
  </table>

  <!-- Document Title -->
  <div class="doc-title">Appointment Letter</div>

  <!-- Salutation & Date -->
  <table class="meta-table" cellpadding="0" cellspacing="0">
    <tr>
      <td class="salutation">Dear {{ $applicant_name }},</td>
      <td class="meta-date">Date: {{ $issue_date }}</td>
    </tr>
  </table>

  <p>
    We are pleased to appoint you as an <strong>{{ $designation }}</strong> at <strong>Blueboxx DA Pvt. Ltd.</strong> This appointment is effective from <strong>{{ $start_date }}</strong>.
  </p>

  <p>
    Based on your application, interview performance, and evaluation of your skills, we believe that your abilities & enthusiasm will be a valuable addition to our organization. We look forward to a mutually beneficial professional association.
  </p>

  <!-- Appointment Details -->
  <div class="section-title">Appointment Details:</div>
  <ul class="bullet-list">
    <li><strong>Designation:</strong> {{ $designation }}</li>
    <li><strong>Department / Domain:</strong> {{ $department }}</li>
    <li><strong>Employment Type:</strong> {{ $employment_type ?? 'Internship / Trainee Appointment' }}</li>
    <li><strong>Work Mode:</strong> {{ $mode ?? 'Fully Remote' }}</li>
    <li><strong>Start Date:</strong> {{ $start_date }}</li>
    <li><strong>End Date:</strong> {{ $end_date ?? 'To be determined based on performance / milestone review' }}</li>
  </ul>

  <!-- Roles and Responsibilities -->
  <div class="section-title">Roles and Responsibilities:</div>
  <ul class="bullet-list">
    @if(!empty($responsibilities) && is_array($responsibilities))
      @foreach($responsibilities as $resp)
        <li>{{ $resp }}</li>
      @endforeach
    @else
      <li>Assist in planning and executing domain-specific project milestones and deliverables.</li>
      <li>Create, develop, and maintain high-quality project assets in accordance with quality standards.</li>
      <li>Participate in daily sprint standups, milestone reviews, and maintain consistent task updates.</li>
      <li>Perform research, troubleshooting, and optimization for assigned application workflows.</li>
      <li>Coordinate with cross-functional design, development, and management mentors.</li>
      <li>Follow proper version control, documentation, and reporting protocols on the internal platform.</li>
      <li>Maintain strict confidentiality of company and client source codes, data, and project assets.</li>
      <li>Complete assigned tasks on time and maintain professional communication across all channels.</li>
    @endif
  </ul>

  <!-- Performance Review and Stipend -->
  <div class="section-title">Performance Review and Stipend:</div>
  <p>
    Your performance will be reviewed periodically based on the quality of work, adherence to deadlines, and contribution to team objectives.
  </p>
  <p>
    You will be eligible for a performance-based stipend of <strong>{{ $stipend }}</strong>@if(!empty($duration_text)) for {{ $duration_text }}@endif, which may be revised based on your performance and management evaluation.
  </p>

  <!-- Bottom Left Accent -->
  <div class="corner-bl">
    <div class="corner-bl-navy"></div>
    <div class="corner-bl-orange"></div>
  </div>

  <!-- Footer Contact Bar -->
  <div class="footer-bar">
    <div class="footer-bar-contact">
      📞 +91-7798575777 &nbsp;&nbsp;|&nbsp;&nbsp; ✉ info.blueboxx@gmail.com &nbsp;&nbsp;|&nbsp;&nbsp; 🌐 www.blueboxx.in
    </div>
    <div class="footer-bar-address">
      SF-02, Indiabulls, Mega Mall Nr. Jetalpur, Bridge, Akota Road, Akota, Vadodara
    </div>
  </div>
</div>


<!-- ================= PAGE 2 ================= -->
<div class="page" style="page-break-before: always;">
  <!-- Top Right Accent -->
  <div class="corner-tr">
    <div class="corner-tr-orange"></div>
    <div class="corner-tr-navy"></div>
  </div>

  <!-- Header / Logo -->
  <table class="header-table" cellpadding="0" cellspacing="0">
    <tr>
      <td style="vertical-align: middle; width: 60px;">
        @if(!empty($logo_base64))
          <img src="{{ $logo_base64 }}" style="max-height: 48px; max-width: 50px;" alt="Logo" />
        @else
          <div style="width: 44px; height: 44px; background: #1B2A6B; border-radius: 8px; text-align: center; line-height: 44px; color: #fff; font-weight: bold; font-size: 18px;">BB</div>
        @endif
      </td>
      <td style="vertical-align: middle; padding-left: 10px;">
        <div class="logo-title">BLUEBOXX DA</div>
        <div class="logo-subtitle">ADVERTISING | TRAINING | PLACEMENT | ANIMATION</div>
      </td>
    </tr>
  </table>

  <!-- Conduct, Confidentiality, and Compliance -->
  <div class="section-title" style="margin-top: 0;">Conduct, Confidentiality, and Compliance:</div>
  <p>
    You are expected to maintain professional conduct, protect confidential company information, and comply with all policies and guidelines of <strong>Blueboxx DA Pvt. Ltd.</strong>
  </p>
  <p>
    Any violation of company policies may lead to disciplinary action or termination of this appointment.
  </p>

  <!-- Recognition and Growth Opportunities -->
  <div class="section-title">Recognition and Growth Opportunities:</div>
  <p style="margin-bottom: 6px;">Upon successful completion of your tenure, you may be eligible for:</p>
  <ul class="bullet-list">
    <li><strong>Recognition Letter:</strong> Acknowledging your contribution and role during the internship.</li>
    <li><strong>Experience Letter:</strong> Detailing your responsibilities and accomplishments.</li>
    <li><strong>Recommendation Letter:</strong> Offered to top-performing interns based on dedication, performance, and overall contribution throughout the internship.</li>
    <li><strong>Team Leadership Letter:</strong> Awarded to interns who successfully take up leadership roles and demonstrate effective team coordination and management.</li>
    <li><strong>Job Assistance:</strong> Support in improving employability through career guidance and mentorship.</li>
    <li><strong>Performance and Rewards:</strong> The Top performers during the internship will receive exclusive goodies and a printed certificate as a token of appreciation and encouragement.</li>
    <li><strong>Exposure and Learning:</strong> Opportunity to work on live projects and enhance your professional skill set.</li>
    <li><strong>Expectations and Conduct:</strong> We expect you to adhere to the company's policies, maintain confidentiality, and demonstrate a professional attitude throughout the internship. Any breach of policies may result in termination of the internship.</li>
  </ul>

  <!-- Acceptance of Appointment -->
  <div class="section-title">Acceptance of Appointment:</div>
  <p>
    Please confirm your acceptance of this appointment by signing and returning a copy of this letter within 3 (three) days from the date of issuance.
  </p>
  <p>
    We welcome you to <strong>Blueboxx DA Pvt. Ltd.</strong> and wish you a successful and rewarding professional journey with us.
  </p>

  <!-- Acknowledgment and Acceptance (Candidate ONLY) -->
  <div class="section-title" style="margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
    Acknowledgment and Acceptance
  </div>
  <p style="font-style: italic; color: #4a5568; margin-bottom: 12px;">
    I hereby acknowledge and accept the terms and conditions of this appointment letter.
  </p>

  <!-- ONLY CANDIDATE SIGNATURE SECTION -->
  <div class="signature-container">
    <div style="font-size: 11.5px; font-weight: bold; color: #0d1635; margin-bottom: 4px;">
      Signature:
    </div>
    <div class="sig-img-box">
      @if(!empty($candidate_signature_data))
        <img src="{{ $candidate_signature_data }}" class="sig-img" alt="Candidate Signature" />
      @else
        <span class="sig-line-blank"></span>
      @endif
    </div>

    <table cellpadding="0" cellspacing="0" style="margin-top: 4px; font-size: 11px; line-height: 1.6;">
      <tr>
        <td style="font-weight: bold; color: #0d1635; width: 60px;">Name:</td>
        <td style="color: #1a202c;">{{ $applicant_name }}</td>
      </tr>
      <tr>
        <td style="font-weight: bold; color: #0d1635;">Date:</td>
        <td style="color: #1a202c;">{{ $signed_at ?? $issue_date }}</td>
      </tr>
    </table>
  </div>

  <!-- Bottom Left Accent -->
  <div class="corner-bl">
    <div class="corner-bl-navy"></div>
    <div class="corner-bl-orange"></div>
  </div>

  <!-- Footer Contact Bar -->
  <div class="footer-bar">
    <div class="footer-bar-contact">
      📞 +91-7798575777 &nbsp;&nbsp;|&nbsp;&nbsp; ✉ info.blueboxx@gmail.com &nbsp;&nbsp;|&nbsp;&nbsp; 🌐 www.blueboxx.in
    </div>
    <div class="footer-bar-address">
      SF-02, Indiabulls, Mega Mall Nr. Jetalpur, Bridge, Akota Road, Akota, Vadodara
    </div>
  </div>
</div>

</body>
</html>
