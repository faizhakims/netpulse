<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>NetPulse | Privacy Policy</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- CSS Terpisah --}}
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        .privacy-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: #1e3b26ff;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .privacy-container h1 {
            color: #f8fafc;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .privacy-container h2 {
            color: #f1f5f9;
            font-size: 1.25rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .privacy-container p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main-content">
        <div class="privacy-container fade-in-up">
            <h1>Privacy Policy</h1>
            <p><strong>Effective Date:</strong> {{ date('F d, Y') }}</p>
            
            <h2>1. Introduction</h2>
            <p>Welcome to NetPulse Network Operations Center ("NetPulse," "we," "our," or "us"). We are committed to safeguarding the privacy and security of the data entrusted to us. This Privacy Policy outlines our practices regarding the collection, use, processing, and protection of information within our monitoring infrastructure.</p>
            
            <h2>2. Scope of Data Collection</h2>
            <p>In the course of providing our network monitoring and telemetry services, we may collect and process specific technical data. This includes, but is not limited to, network traffic metadata, IP addresses, device identifiers, system logs, and operational performance metrics. We do not actively collect personally identifiable information (PII) unless it is strictly necessary for authentication and authorization purposes within the NetPulse platform.</p>
            
            <h2>3. Purpose and Use of Data</h2>
            <p>The technical data we collect is utilized exclusively to ensure the reliability, performance, and security of the monitored infrastructure. Specifically, this data enables us to perform proactive alerting, incident response, traffic analysis, and system health evaluations. Under no circumstances will this operational data be sold, rented, or shared with unauthorized third parties.</p>
            
            <h2>4. Data Security and Retention</h2>
            <p>We employ industry-standard cryptographic protocols, access controls, and strict internal policies to protect your data against unauthorized access, alteration, or disclosure. Data retention policies are enforced to ensure that system logs and telemetry data are retained only for the duration required to meet operational compliance and auditing standards, after which they are securely purged.</p>
            
            <h2>5. Contact Information</h2>
            <p>If you require further clarification regarding our data handling practices, or if you wish to exercise your rights pertaining to your data, please contact the NetPulse Security and Compliance Team through your designated administrative channel.</p>
        </div>

        @include('partials.footer')
    </main>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, i * 60);
                }
            });
        }, { threshold: 0.08 });
        document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));
    </script>
</body>
</html>
