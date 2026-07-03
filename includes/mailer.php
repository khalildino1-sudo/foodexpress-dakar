<?php
/**
 * Helper d'envoi d'emails via PHPMailer
 * Nécessite l'installation de PHPMailer dans /vendor/PHPMailer/src/
 * (télécharge depuis : https://github.com/PHPMailer/PHPMailer/releases)
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Envoyer un email HTML
 */
function sendMail($to, $subject, $htmlBody, $textBody = '') {
    // Charger PHPMailer
    $base = __DIR__ . '/../vendor/PHPMailer/src/';
    if (!file_exists($base . 'PHPMailer.php')) {
        // PHPMailer non installé : on log et on continue sans bloquer
        error_log("PHPMailer non installé. Email vers $to non envoyé : $subject");
        return false;
    }

    require_once $base . 'Exception.php';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
         // 👇 AJOUTE CE BLOC (contourne l'erreur SSL sur WAMP en local)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Template HTML d'email avec branding FoodExpress
 */
function emailTemplate($title, $contentHtml) {
    return '
    <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;background:#f8f9fa;">
        <div style="background:#b7102a;padding:30px 20px;text-align:center;">
            <div style="display:inline-block;width:50px;height:50px;background:white;border-radius:12px;line-height:50px;font-size:24px;color:#b7102a;font-weight:bold;">F</div>
            <h1 style="color:white;margin:15px 0 5px;font-size:24px;">FoodExpress Dakar</h1>
            <p style="color:rgba(255,255,255,0.9);margin:0;font-size:13px;">Le goût authentique du Sénégal</p>
        </div>
        <div style="background:white;padding:40px 30px;">
            <h2 style="color:#191c1d;margin:0 0 20px;font-size:20px;">' . htmlspecialchars($title) . '</h2>
            ' . $contentHtml . '
        </div>
        <div style="background:#2e3132;padding:20px;text-align:center;color:rgba(255,255,255,0.7);font-size:12px;">
            <p style="margin:0 0 5px;">© ' . date('Y') . ' FoodExpress Dakar. Tous droits réservés.</p>
            <p style="margin:0;">Plateau, Dakar · ' . APP_PHONE . '</p>
        </div>
    </div>';
}

/**
 * Email : confirmation de commande
 * @param array $commande  numero, sous_total, frais_livraison, reduction, total, adresse_livraison, quartier, telephone
 * @param array $user      ligne BDD users (email, prenom, nom...)
 * @param array $items     liste [nom_plat, quantite, sous_total]
 */
function sendOrderConfirmation($commande, $user, $items) {
    $email = $user['email'] ?? '';
    $nom   = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
    if (!$email) return false;

    $itemsHtml = '<table style="width:100%;border-collapse:collapse;margin:20px 0;">';
    foreach ($items as $d) {
        $itemsHtml .= '<tr style="border-bottom:1px solid #eee;">';
        $itemsHtml .= '<td style="padding:10px 0;"><strong>' . htmlspecialchars($d['nom_plat']) . '</strong><br><small style="color:#666;">x' . $d['quantite'] . '</small></td>';
        $itemsHtml .= '<td style="padding:10px 0;text-align:right;">' . number_format($d['sous_total'], 0, ',', ' ') . ' FCFA</td>';
        $itemsHtml .= '</tr>';
    }
    $itemsHtml .= '</table>';

    $content = '
        <p>Bonjour <strong>' . htmlspecialchars($nom) . '</strong>,</p>
        <p>Merci pour votre commande ! Nous avons bien reçu votre demande et nous la préparons avec soin.</p>
        <div style="background:#f3f4f5;padding:20px;border-radius:8px;margin:20px 0;">
            <div style="font-size:13px;color:#5b403f;">Numéro de commande</div>
            <div style="font-size:24px;font-weight:bold;color:#b7102a;">' . htmlspecialchars($commande['numero']) . '</div>
        </div>
        <h3 style="font-size:16px;color:#191c1d;">Votre commande</h3>
        ' . $itemsHtml . '
        <table style="width:100%;margin-top:20px;">
            <tr><td>Sous-total</td><td style="text-align:right;">' . number_format($commande['sous_total'], 0, ',', ' ') . ' FCFA</td></tr>
            <tr><td>Livraison</td><td style="text-align:right;">' . number_format($commande['frais_livraison'], 0, ',', ' ') . ' FCFA</td></tr>
            <tr style="font-weight:bold;font-size:18px;color:#b7102a;"><td style="padding-top:10px;border-top:2px solid #b7102a;">Total</td><td style="text-align:right;padding-top:10px;border-top:2px solid #b7102a;">' . number_format($commande['total'], 0, ',', ' ') . ' FCFA</td></tr>
        </table>
        <div style="margin:30px 0;padding:15px;background:#e4f7f3;border-left:4px solid #00685d;border-radius:4px;">
            <strong style="color:#005048;">📍 Livraison</strong><br>
            ' . htmlspecialchars($commande['adresse_livraison']) . '<br>
            ' . htmlspecialchars($commande['quartier']) . ' · ' . htmlspecialchars($commande['telephone']) . '
        </div>
        <p>Vous recevrez une notification dès que votre commande sera en livraison.</p>
        <p style="margin-top:30px;color:#5b403f;font-size:13px;">À très vite,<br><strong>L\'équipe FoodExpress Dakar</strong></p>
    ';
    return sendMail($email, 'Confirmation de commande ' . $commande['numero'], emailTemplate('Commande confirmée !', $content));
}

/**
 * Email : réinitialisation mot de passe
 */
function sendPasswordReset($email, $nom, $token) {
    $link = APP_URL . '/auth/reset-password.php?token=' . urlencode($token);
    $content = '
        <p>Bonjour <strong>' . htmlspecialchars($nom) . '</strong>,</p>
        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
        <div style="text-align:center;margin:30px 0;">
            <a href="' . $link . '" style="display:inline-block;background:#b7102a;color:white;text-decoration:none;padding:14px 30px;border-radius:10px;font-weight:bold;">Réinitialiser mon mot de passe</a>
        </div>
        <p style="font-size:13px;color:#5b403f;">Ce lien expire dans 1 heure. Si vous n\'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
    ';
    return sendMail($email, 'Réinitialisation de mot de passe', emailTemplate('Réinitialiser votre mot de passe', $content));
}
