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
    font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
  }
  html, body {
    margin: 0;
    padding: 0;
    background: #ffffff;
    font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
    color: #1e293b;
    font-size: 9.8px;
    line-height: 1.45;
  }

  /* Fixed Background Image repeated on EVERY page */
  .bg-letterhead {
    position: fixed;
    top: 0;
    left: 0;
    width: 210mm;
    height: 297mm;
    z-index: -1000;
  }

  /* Content area padded to fit inside the letterhead safe zone */
  .content-page {
    position: relative;
    padding-top: 36mm;
    padding-bottom: 24mm;
    padding-left: 15mm;
    padding-right: 15mm;
    box-sizing: border-box;
  }

  .page-break {
    page-break-before: always;
  }

  /* Document Title */
  .doc-title {
    text-align: center;
    font-size: 18px;
    font-weight: 900;
    color: #0d1635;
    letter-spacing: 0.3px;
    margin-bottom: 12px;
  }

  /* Meta info row */
  .meta-table {
    width: 100%;
    margin-bottom: 8px;
    font-size: 10.5px;
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
    font-size: 9.8px;
    line-height: 1.45;
    color: #334155;
    margin-bottom: 6px;
    text-align: justify;
  }

  .section-title {
    font-size: 10.8px;
    font-weight: bold;
    color: #0d1635;
    margin-top: 8px;
    margin-bottom: 4px;
  }

  /* List Table for Crisp Bullets */
  .list-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
  }
  .list-table td.bullet {
    width: 12px;
    vertical-align: top;
    color: #1B2A6B;
    font-size: 12px;
    line-height: 1.1;
    padding-top: 1px;
  }
  .list-table td.text {
    vertical-align: top;
    font-size: 9.8px;
    line-height: 1.4;
    color: #334155;
    padding-bottom: 2px;
  }

  /* Signature Box (Candidate Only) */
  .signature-container {
    margin-top: 8px;
    width: 260px;
  }
  .sig-img-box {
    height: 44px;
    margin: 2px 0;
  }
  .sig-img {
    max-height: 42px;
    max-width: 150px;
    display: block;
  }
  .sig-line-blank {
    display: inline-block;
    width: 160px;
    border-bottom: 1.2px solid #334155;
    margin-top: 28px;
  }
</style>
</head>
<body>

@if(!empty($letterhead_bg_base64))
  <!-- Exact uploaded letterhead image placed at full bleed (0, 0 to 210mm, 297mm) on EVERY page -->
  <img src="{{ $letterhead_bg_base64 }}" class="bg-letterhead" alt="Blueboxx Letterhead" />
@endif

<!-- ================= PAGE 1 ================= -->
<div class="content-page">
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
  <table class="list-table" cellpadding="0" cellspacing="0">
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Designation:</strong> {{ $designation }}</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Department / Domain:</strong> {{ $department }}</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Employment Type:</strong> {{ $employment_type ?? 'Internship / Trainee Appointment' }}</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Work Mode:</strong> {{ $mode ?? 'Fully Remote' }}</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Start Date:</strong> {{ $start_date }}</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>End Date:</strong> {{ $end_date ?? 'To be determined based on performance / milestone review' }}</td>
    </tr>
  </table>

  <!-- Roles and Responsibilities -->
  <div class="section-title">Roles and Responsibilities:</div>
  <table class="list-table" cellpadding="0" cellspacing="0">
    @if(!empty($responsibilities) && is_array($responsibilities))
      @foreach($responsibilities as $resp)
        <tr>
          <td class="bullet">&bull;</td>
          <td class="text">{{ $resp }}</td>
        </tr>
      @endforeach
    @else
      <tr><td class="bullet">&bull;</td><td class="text">Design, build, and maintain efficient, reusable, and reliable software components.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Develop and integrate RESTful APIs and modern database schemas as per technical specs.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Troubleshoot, debug, and optimize application speed, scalability, and security.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Collaborate closely with UI/UX designers, product managers, and senior engineers.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Write modular, clean code and maintain standard Git version control best practices.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Perform unit testing and participate in peer code review cycles.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Log daily progress, tasks, and milestone updates on the internal BlueBoxx tracking system.</td></tr>
      <tr><td class="bullet">&bull;</td><td class="text">Participate in agile standups, complete assigned tasks, and maintain professional communication.</td></tr>
    @endif
  </table>

  <!-- Performance Review and Stipend -->
  <div class="section-title">Performance Review and Stipend:</div>
  <p>
    Your performance will be reviewed periodically based on the quality of work, adherence to deadlines, and contribution to team objectives.
  </p>
  <p>
    You will be eligible for a performance-based stipend of <strong>{{ $stipend }}</strong>@if(!empty($duration_text)) for {{ $duration_text }}@endif, which may be revised based on your performance and management evaluation.
  </p>
</div>

<!-- ================= PAGE 2 ================= -->
<div class="content-page page-break">
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
  <p style="margin-bottom: 4px;">Upon successful completion of your tenure, you may be eligible for:</p>
  <table class="list-table" cellpadding="0" cellspacing="0">
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Recognition Letter:</strong> Acknowledging your contribution and role during the internship.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Experience Letter:</strong> Detailing your responsibilities and accomplishments.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Recommendation Letter:</strong> Offered to top-performing interns based on dedication, performance, and overall contribution throughout the internship.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Team Leadership Letter:</strong> Awarded to interns who successfully take up leadership roles and demonstrate effective team coordination and management.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Job Assistance:</strong> Support in improving employability through career guidance and mentorship.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Performance and Rewards:</strong> The Top performers during the internship will receive exclusive goodies and a printed certificate as a token of appreciation and encouragement.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Exposure and Learning:</strong> Opportunity to work on live projects and enhance your professional skill set.</td>
    </tr>
    <tr>
      <td class="bullet">&bull;</td>
      <td class="text"><strong>Expectations and Conduct:</strong> We expect you to adhere to the company's policies, maintain confidentiality, and demonstrate a professional attitude throughout the internship. Any breach of policies may result in termination of the internship.</td>
    </tr>
  </table>

  <!-- Acceptance of Appointment -->
  <div class="section-title">Acceptance of Appointment:</div>
  <p>
    Please confirm your acceptance of this appointment by signing and returning a copy of this letter within 3 (three) days from the date of issuance.
  </p>
  <p>
    We welcome you to <strong>Blueboxx DA Pvt. Ltd.</strong> and wish you a successful and rewarding professional journey with us.
  </p>

  <!-- Acknowledgment and Acceptance (Candidate ONLY) -->
  <div class="section-title" style="margin-top: 8px; border-top: 1px solid #cbd5e1; padding-top: 6px;">
    Acknowledgment and Acceptance
  </div>
  <p style="font-style: italic; color: #475569; margin-bottom: 6px;">
    I hereby acknowledge and accept the terms and conditions of this appointment letter.
  </p>

  <!-- ONLY CANDIDATE SIGNATURE SECTION -->
  <div class="signature-container">
    <div style="font-size: 10.5px; font-weight: bold; color: #0d1635; margin-bottom: 2px;">
      Signature:
    </div>
    <div class="sig-img-box">
      @if(!empty($candidate_signature_data))
        <img src="{{ $candidate_signature_data }}" class="sig-img" alt="Candidate Signature" />
      @else
        <span class="sig-line-blank"></span>
      @endif
    </div>

    <table cellpadding="0" cellspacing="0" style="margin-top: 2px; font-size: 10px; line-height: 1.45;">
      <tr>
        <td style="font-weight: bold; color: #0d1635; width: 45px;">Name:</td>
        <td style="color: #1e293b;">{{ $applicant_name }}</td>
      </tr>
      <tr>
        <td style="font-weight: bold; color: #0d1635;">Date:</td>
        <td style="color: #1e293b;">{{ $signed_at ?? $issue_date }}</td>
      </tr>
    </table>
  </div>
</div>

</body>
</html>
