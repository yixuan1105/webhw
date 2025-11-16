<?php
// 這是臨時腳本，用於生成密碼的 Hash 值

$admin_password = 'password'; // 管理員的原始密碼
$student_password = 'pw1';    // 學生 A 的原始密碼

// 使用 password_hash 函式生成安全的 Hash 值
$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$student_hash = password_hash($student_password, PASSWORD_DEFAULT);

echo "管理員 M (admin_M) 的 Hash 值是: \n" . $admin_hash . "\n\n";
echo "學生 A (student_A) 的 Hash 值是: \n" . $student_hash . "\n\n";
?>