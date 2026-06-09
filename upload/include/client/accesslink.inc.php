<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = 'Получить ссылку на email';
else
    $button = 'Открыть заявку';
?>
<h1>Проверить статус заявки</h1>
<p><?php
echo 'Укажите ваш email и номер заявки.';
if ($cfg->isClientEmailVerificationRequired())
    echo ' Ссылка для доступа будет отправлена на ваш email.';
else
    echo ' Вы будете авторизованы и сможете просмотреть заявку.';
?></p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div class="login-box">
    <div><strong><?php echo Format::htmlchars($errors['login']); ?></strong></div>
    <div>
        <label for="email">Электронная почта:
        <input id="email" placeholder="например, ivanov@example.com" type="text"
            name="lemail" size="30" value="<?php echo $email; ?>" class="nowarn"></label>
    </div>
    <div>
        <label for="ticketno">Номер заявки:
        <input id="ticketno" type="text" name="lticket" placeholder="например, 051243"
            size="30" value="<?php echo $ticketid; ?>" class="nowarn"></label>
    </div>
    <p>
        <input class="btn" type="submit" value="<?php echo $button; ?>">
    </p>
    </div>
    <div class="instructions">
<?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
        Уже есть аккаунт?
        <a href="login.php">Войти</a> <?php
    if ($cfg->isClientRegistrationEnabled()) { ?>
<?php echo sprintf('или %sзарегистрируйтесь%s, чтобы получить доступ ко всем заявкам.',
    '<a href="account.php?do=create">','</a>');
    }
}?>
    </div>
</div>
</form>
<br>
<p>
<?php
if ($cfg->getClientRegistrationMode() != 'disabled'
    || !$cfg->isClientLoginRequired()) {
    echo sprintf(
    'Если вы обращаетесь впервые или потеряли номер заявки, %sсоздайте новую заявку%s.',
        '<a href="open.php">','</a>');
} ?>
</p>
