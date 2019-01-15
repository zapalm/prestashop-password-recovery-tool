<?php
/**
 * Employee password recovery tool for PrestaShop CMS.
 *
 * @author    Maksim T. <zapalm@yandex.com>
 * @copyright 2018 Maksim T.
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/zapalm/prestashop-password-recovery-tool GitHub.
 * @link      https://prestashop.modulez.ru/en/tools-scripts/54-prestashop-password-recovery-tool.html Homepage.
 *
 * @version 1.0.0
 */

$configPath = dirname(__FILE__) . '/config/config.inc.php';
if (false === file_exists($configPath)) {
    $fatalError = 'The file of the tool is placed incorrectly. You should place the file to the root of your PrestaShop installation directory.';
} else {
    require_once $configPath;

    $email        = Tools::getValue('email', 'admin@example.com');
    $password     = Tools::getValue('password', '1234567890');
    $passwordHash = Tools::encrypt($password);
    $firstName    = 'Admin';
    $lastName     = 'Admin';
    $idLanguage   = (int)Configuration::get('PS_LANG_DEFAULT');
    $isSubmit     = Tools::isSubmit('recover');
    $isSuccess    = false;
    $fatalError   = false;

    $errors = array();
    if (false === Validate::isEmail($email)) {
        $errors[] = 'The e-mail is incorrect.';
    }
    if (false === Validate::isPasswd($password, 5)) {
        $errors[] = 'The password is incorrect or weak.';
    }

    if (0 === count($errors) && $isSubmit) {
        if (Employee::employeeExists($email)) {
            $isSuccess = (false !== Db::getInstance()->execute('
                UPDATE ' . _DB_PREFIX_ . 'employee
                SET
                    passwd = "' . pSQL($passwordHash) . '",
                    active = 1
                WHERE email = "' . pSQL($email) . '"
            '));
        } else {
            $accountData = array_filter(array(
                'id_profile'  => _PS_ADMIN_PROFILE_,
                'id_lang'     => (version_compare(_PS_VERSION_, '1.4', '>=') ? $idLanguage : null),
                'default_tab' => (version_compare(_PS_VERSION_, '1.5', '>=') ? 1 : null),
                'active'      => 1,
                'lastname'    => '"' . pSQL($lastName) . '"',
                'firstname'   => '"' . pSQL($firstName) . '"',
                'email'       => '"' . pSQL($email) . '"',
                'passwd'      => '"' . pSQL($passwordHash) . '"',
            ));

            $isSuccess = (false !== Db::getInstance()->execute('
                INSERT INTO ' . _DB_PREFIX_ . 'employee (' . implode(',', array_keys($accountData)) . ')
                VALUES (' . implode(',', $accountData) . ')
            '));

            if ($isSuccess) {
                $isAssociatedToShop = (1 === (int)Db::getInstance()->getValue('
                    SELECT 1
                    FROM information_schema.tables 
                    WHERE table_schema =  "' . _DB_NAME_ . '" AND table_name = "' . _DB_PREFIX_ . 'employee_shop"
                '));

                if ($isAssociatedToShop) {
                    $employeeId = (int)Db::getInstance()->getValue('
                        SELECT id_employee
                        FROM ' . _DB_PREFIX_ . 'employee
                        WHERE email = "' . pSQL($email) . '"
                    ');

                    Db::getInstance()->execute('
                        INSERT INTO ' . _DB_PREFIX_ . 'employee_shop (id_employee, id_shop)
                        VALUES (' . $employeeId . ', ' . (int)Configuration::get('PS_SHOP_DEFAULT') . ') 
                    ');
                }
            }
        }

        if (false === $isSuccess) {
            $errors[] = 'Saving to database problem.';
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>PrestaShop password recovery tool</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <style type="text/css">
        :root {
            --input-padding-x: .75rem;
            --input-padding-y: .75rem;
        }

        html,
        body {
            height: 100%;
        }

        body {
            display: -ms-flexbox;
            display: -webkit-box;
            display: flex;
            -ms-flex-align: center;
            -ms-flex-pack: center;
            -webkit-box-align: center;
            align-items: center;
            -webkit-box-pack: center;
            justify-content: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .form-recover {
            width: 100%;
            max-width: 420px;
            padding: 15px;
            margin: 0 auto;
        }

        .form-label-group {
            position: relative;
            margin-bottom: 1rem;
        }

        .form-label-group > input,
        .form-label-group > label {
            padding: var(--input-padding-y) var(--input-padding-x);
        }

        .form-label-group > label {
            position: absolute;
            top: 0;
            left: 0;
            display: block;
            width: 100%;
            margin-bottom: 0; /* Override default `<label>` margin */
            line-height: 1.5;
            color: #495057;
            border: 1px solid transparent;
            border-radius: .25rem;
            transition: all .1s ease-in-out;
        }

        .form-label-group input::-webkit-input-placeholder {
            color: transparent;
        }

        .form-label-group input:-ms-input-placeholder {
            color: transparent;
        }

        .form-label-group input::-ms-input-placeholder {
            color: transparent;
        }

        .form-label-group input::-moz-placeholder {
            color: transparent;
        }

        .form-label-group input::placeholder {
            color: transparent;
        }

        .form-label-group input:not(:placeholder-shown) {
            padding-top: calc(var(--input-padding-y) + var(--input-padding-y) * (2 / 3));
            padding-bottom: calc(var(--input-padding-y) / 3);
        }

        .form-label-group input:not(:placeholder-shown) ~ label {
            padding-top: calc(var(--input-padding-y) / 3);
            padding-bottom: calc(var(--input-padding-y) / 3);
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
<form class="form-recover" method="post">
    <div class="text-center mb-4">
        <img class="mb-4" src="https://prestashop.modulez.ru/img/cms/zapalm-300x300.jpg" alt="zapalm" width="72" height="72">
        <h1 class="h4 mb-3 font-weight-normal">PrestaShop password recovery tool</h1>
        <p>
            By this tool you can recover your existent employee account or create a new.
            <strong>After recovery, do not forget to delete the tool.</strong>
        </p>
    </div>

    <?php if (false !== $fatalError): ?>
        <div class="alert alert-danger" role="alert"><?= $fatalError ?></div>
    <?php else: ?>
        <?php if ($isSubmit && $isSuccess): ?>
            <div class="alert alert-success" role="alert">Done!</div>
        <?php elseif ($isSubmit && false === $isSuccess): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert"><?= $error ?></div>
            <?php endforeach ?>
        <?php endif ?>

        <div class="form-label-group">
            <input name="email" type="email" id="email" class="form-control" placeholder="Existent or new e-mail" required autofocus value="<?= $email?>">
            <label for="email">Existent or new e-mail</label>
        </div>

        <div class="form-label-group">
            <input name="password" type="text" id="password" class="form-control" placeholder="New password" required value="<?= $password ?>">
            <label for="password">New password</label>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit" name="recover">Recover or create</button>
    <?php endif ?>

    <p class="mt-5 mb-3 text-muted text-center">
        &copy; 2018 Maksim T. &lt;zapalm@yandex.com&gt;
        <br>
        <a href="https://prestashop.modulez.ru/en/14-tools-scripts" target="_blank">Free tools for PrestaShop</a>
    </p>
</form>
</body>
</html>
