<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Appointment Letter - {{ $reference_number }}</title>
<style>
  @page {
    size: a4 portrait;
    margin: 0;
  }
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'DejaVu Sans', sans-serif;
  }
  body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 11.4px;
    line-height: 1.62;
    color: #1a202c;
    background: #ffffff;
    width: 210mm;
    height: 297mm;
    position: relative;
    overflow: hidden;
  }

  /* Full Page Official Background Letterhead */
  .letterhead-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 210mm;
    height: 297mm;
    z-index: 1;
  }

  /* Document Body Content positioned between letterhead top and bottom */
  .content-wrapper {
    position: relative;
    z-index: 10;
    padding-top: 136px;
    padding-bottom: 75px;
    padding-left: 50px;
    padding-right: 50px;
  }

  .doc-title {
    text-align: center;
    font-size: 15.5px;
    font-weight: bold;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #0d1b3e;
    margin-bottom: 15px;
  }

  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 11px;
  }

  .recipient-block {
    margin-bottom: 14px;
    font-size: 11.4px;
    line-height: 1.5;
  }

  .subject-line {
    font-size: 12px;
    font-weight: bold;
    color: #0d1b3e;
    margin-bottom: 14px;
  }

  p {
    margin-bottom: 12px;
    text-align: justify;
    font-size: 11.4px;
    line-height: 1.62;
  }

  ul.clauses-list {
    margin: 6px 0 14px 22px;
    padding: 0;
  }

  ul.clauses-list li {
    font-size: 11.4px;
    line-height: 1.55;
    margin-bottom: 7px;
  }

  .signature-block {
    margin-top: 18px;
  }

  .sig-img-container {
    height: 64px;
    margin: 4px 0;
  }

  .sig-img {
    max-height: 60px;
    max-width: 160px;
    display: block;
  }

  .sig-text-stamp {
    font-size: 11px;
    font-style: italic;
    color: #0d1b3e;
    font-weight: bold;
    padding-top: 18px;
  }

  .sig-line {
    width: 240px;
    border-top: 1.2px solid #374151;
    padding-top: 4px;
    font-size: 10px;
    line-height: 1.35;
  }

  .badge-signed {
    font-size: 9px;
    color: #059669;
    font-weight: bold;
  }
</style>
</head>
<body>

@if(!empty($letterhead_bg))
  <img src="{{ $letterhead_bg }}" class="letterhead-bg" alt="Letterhead Background" />
@endif

<div class="content-wrapper">
  
  <div class="doc-title">Appointment Letter</div>

  <table class="meta-table">
    <tr>
      <td style="text-align: left;"><strong>Ref No:</strong> {{ $reference_number }}</td>
      <td style="text-align: right;"><strong>Date:</strong> {{ $issue_date }}</td>
    </tr>
  </table>

  <div class="recipient-block">
    <strong>To,</strong><br>
    <strong style="font-size: 12px; color: #0d1b3e;">{{ $applicant_name }}</strong><br>
    @if(!empty($degree))<span>{{ $degree }}</span>@if(!empty($college)), <span>{{ $college }}</span>@endif<br>@endif
    <span>{{ $location }}</span> | <span>{{ $applicant_email }}</span> | <span>{{ $applicant_phone }}</span>
  </div>

  <div class="subject-line">
    Subject: Appointment as {{ $designation }}
  </div>

  <p>Dear <strong>{{ $applicant_name }}</strong>,</p>

  <p>
    We are pleased to appoint you as a <strong>{{ $designation }}</strong> (Department: {{ $department }}) at <strong>{{ $company_name }}</strong>, effective <strong>{{ $start_date }}</strong>@if(!empty($end_date)) until <strong>{{ $end_date }}</strong>@endif. You will report to the designated <strong>{{ $reporting_to }}</strong>@if(!empty($reporting_person_name)) ({{ $reporting_person_name }})@endif.
  </p>

  <p>
    Your initial place of work will be <strong>{{ $location }}</strong> ({{ $mode }}), although transfers may occur as needed to meet business requirements. Working hours will follow company policy (<strong>{{ $working_hours }}, {{ $working_days }}</strong>@if(!empty($break_time)), with lunch/break from {{ $break_time }}@endif).
  </p>

  <ul class="clauses-list">
    <li>Your gross stipend / compensation will be <strong>{{ $stipend }}</strong>, subject to applicable taxes, company regulations, and attendance.</li>
    <li>You will be on a <strong>{{ $duration }}</strong> probationary / internship evaluation period, after which performance will be formally reviewed.</li>
    <li>You are entitled to company-authorized leaves and professional certification upon successful completion of assignments.</li>
  </ul>

  <p>
    This appointment may be terminated by either party with <strong>15 days</strong> written notice or payment in lieu of notice.
  </p>

  <p>
    Kindly sign and return a copy of this letter as confirmation of your acceptance. We welcome you aboard and wish you success in your new role.
  </p>

  <div style="font-size: 11px; font-weight: bold; margin-top: 12px; margin-bottom: 3px;">Warm regards,</div>

  <!-- Authorized Company Signature Block (Candidate signature removed) -->
  <div class="signature-block">
    <div style="font-size: 11px; font-weight: bold; color: #0d1b3e;">
      BLUEBOXX DA PVT. LTD.
    </div>
    <div class="sig-img-container">
      @if(!empty($admin_signature_data))
        <img src="{{ $admin_signature_data }}" class="sig-img" alt="Admin Signature" />
      @else
        <div class="sig-text-stamp">[ Authorized Signatory ]</div>
      @endif
    </div>
    <div class="sig-line">
      <strong>{{ $signatory_name ?? 'Authorized Signatory' }}</strong><br>
      <span style="color: #4b5563;">{{ $signatory_designation ?? 'Director / HR Head' }}</span><br>
      <span class="badge-signed">&#10003; Officially Authorized & Signed</span>
    </div>
  </div>

</div>

</body>
</html>
