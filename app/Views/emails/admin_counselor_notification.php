<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Counselor Registration - Pending Approval</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 16px; }
        .header { background: #060E57; color: #fff; padding: 12px 16px; }
        .content { background: #ffffff; border: 1px solid #e5e7eb; padding: 16px; }
        .footer { color: #6b7280; font-size: 12px; margin-top: 12px; }
        .btn { display: inline-block; background: #0d6efd; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
        .meta { margin: 0; padding: 0; list-style: none; }
        .meta li { margin: 4px 0; }
    </style>
    <!-- This is a simple, safe template; avoid external resources for better deliverability -->
    <?php /** @var string $counselor_id */ ?>
    <?php /** @var string $name */ ?>
    <?php /** @var string $email */ ?>
    <?php /** @var string $admin_dashboard_url */ ?>
    <?php /** Prevent undefined warnings if variables not passed */ ?>
    <?php $counselor_id = $counselor_id ?? ''; ?>
    <?php $name = $name ?? ''; ?>
    <?php $email = $email ?? ''; ?>
    <?php $admin_dashboard_url = $admin_dashboard_url ?? ''; ?>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">New Counselor Registration</h2>
        </div>
        <div class="content">
            <p>A new counselor has submitted their basic information and is awaiting approval.</p>
            <ul class="meta">
                <li><strong>Counselor ID:</strong> <?= esc($counselor_id) ?></li>
                <li><strong>Name:</strong> <?= esc($name) ?></li>
                <li><strong>Email:</strong> <?= esc($email) ?></li>
            </ul>
            <p>Please review and approve this account when ready.</p>
        </div>
        <div class="footer">
            <p>This is an automated message from Counselign.</p>
        </div>
    </div>
</body>
</html>


