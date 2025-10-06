<?php

session_start();
$_SESSION['message'] = ['type' => 'success', 'text' => 'Você saiu!'];

header("Location: /");