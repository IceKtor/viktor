<?php
// выход: только POST с токеном, чтобы чужая страница не могла разлогинить по ссылке
require_once __DIR__ . '/includes/account_page.php';
csrf_require();
auth_logout();
redirect('index.php');
