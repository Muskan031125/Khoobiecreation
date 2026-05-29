<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($subject ?? 'Khoobie Creations') ?></title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <!-- header -->
            <tr><td style="padding:24px 32px;background:#fff;border-bottom:1px solid #f1f5f9;">
                <a href="<?= esc($brand_url ?? 'https://khoobie.com') ?>" style="text-decoration:none;color:#0f172a;">
                    <strong style="font-size:20px;letter-spacing:-0.01em;">Khoobie Creations</strong>
                </a>
            </td></tr>
            <!-- body -->
            <tr><td style="padding:32px;font-size:15px;line-height:1.6;color:#1e293b;">
                <?= $body ?>
            </td></tr>
            <!-- footer -->
            <tr><td style="padding:24px 32px;background:#0f172a;color:#94a3b8;font-size:12px;text-align:center;">
                <p style="margin:0 0 8px;">Bringing unique handmade crafts and creative treasures to your doorstep.</p>
                <p style="margin:0 0 8px;">
                    <a href="mailto:craftykhoobie@gmail.com" style="color:#cbd5e1;">craftykhoobie@gmail.com</a>
                    &nbsp;·&nbsp;
                    <a href="tel:+918899223300" style="color:#cbd5e1;">+91 88992 23300</a>
                </p>
                <p style="margin:0 0 4px;">B-110, Sector 69, Noida, UP 201307</p>
                <p style="margin:8px 0 0;color:#475569;">© <?= date('Y') ?> Khoobie Creations · <a href="<?= esc($brand_url ?? 'https://khoobie.com') ?>" style="color:#94a3b8;">khoobie.com</a></p>
            </td></tr>
        </table>
        <p style="font-size:11px;color:#94a3b8;margin-top:12px;">You're receiving this email because you have an account or recent activity with Khoobie Creations.</p>
    </td></tr>
</table>
</body>
</html>
