<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Lokal Culture</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/vendor.css">
</head>
<body class="dashboard-page">
<header class="site-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav class="header-actions"><a href="/account">Sign in</a></nav></header>
<main class="vendor-form-shell">
    <p class="eyebrow">Maker onboarding</p>
    <h1>Bring your craft here.</h1>
    <p class="form-intro">Applications are reviewed before a shop becomes active.</p>
    <?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="form-success"><?= htmlspecialchars($success) ?> <a href="/account">Return to sign in.</a></p><?php endif; ?>
    <?php if (!$success): ?><form class="vendor-form" method="post" action="/vendor/register">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Your name<input name="name" required maxlength="120" value="<?= htmlspecialchars($formData['name']) ?>"></label>
        <label>Email<input name="email" type="email" required value="<?= htmlspecialchars($formData['email']) ?>"></label>
        <label>Password<input name="password" type="password" minlength="8" required></label>
        <label>Confirm password<input name="password_confirmation" type="password" minlength="8" required></label>
        <label>Shop name<input name="shop_name" required maxlength="150" value="<?= htmlspecialchars($formData['shop_name']) ?>"></label>
        <label>Shop description<textarea name="description" rows="4" maxlength="2000"><?= htmlspecialchars($formData['description']) ?></textarea></label>
        <div class="form-columns"><label>GSTIN<input name="gstin" required maxlength="30" value="<?= htmlspecialchars($formData['gstin']) ?>"></label><label>Postal code<input name="postal_code" required maxlength="20" value="<?= htmlspecialchars($formData['postal_code']) ?>"></label></div>
        <label>Registered address<input name="registered_address" required maxlength="255" value="<?= htmlspecialchars($formData['registered_address']) ?>"></label>
        <label>State<input name="state" required maxlength="100" value="<?= htmlspecialchars($formData['state']) ?>"></label>
        <div class="form-columns"><label>Region<input name="region" maxlength="120" value="<?= htmlspecialchars($formData['region']) ?>"></label><label>Craft specialty<input name="craft_specialty" maxlength="120" value="<?= htmlspecialchars($formData['craft_specialty']) ?>"></label></div>
        <button class="button" type="submit">Submit application <span>→</span></button>
    </form><?php endif; ?>
</main>
</body>
</html>
