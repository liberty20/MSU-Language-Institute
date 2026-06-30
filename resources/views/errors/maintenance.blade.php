<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Offline - MSUNLI</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #0a1f44;
            color: #ffffff;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            max-width: 500px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(245, 194, 66, 0.2);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .logo {
            width: 80px;
            height: auto;
            background: #ffffff;
            padding: 8px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid rgba(245, 194, 66, 0.3);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .badge {
            background-color: rgba(229, 62, 62, 0.1);
            color: #f56565;
            border: 1px solid rgba(229, 62, 62, 0.2);
            padding: 6px 16px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            border-radius: 9999px;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 28px;
            font-weight: 900;
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
        }
        p {
            font-size: 14px;
            color: #cbd5e1;
            line-height: 1.6;
            margin: 0 0 24px 0;
        }
        .contact-info {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 24px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .contact-info a {
            color: #f5c242;
            text-decoration: none;
        }
        .contact-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <img class="logo" src="/msu-logo-2.png" alt="MSU Logo">
        <div>
            <span class="badge">System Offline</span>
            <h1>Under Maintenance</h1>
            <p>
                The Midlands State University National Language Institute portal is currently undergoing scheduled system updates. Please try again later.
            </p>
        </div>
        <div class="contact-info">
            <p style="margin-bottom: 8px;">Administrative Email: <a href="mailto:{{ $contactInfo['email'] ?? 'language.institute@msu.ac.zw' }}">{{ $contactInfo['email'] ?? 'language.institute@msu.ac.zw' }}</a></p>
            <p style="margin: 0;">Support Desk: {{ $contactInfo['phone'] ?? '+263 54 2260331' }}</p>
        </div>
    </div>
</body>
</html>
