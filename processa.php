<?php
session_start();
include_once("conexao.php");

// Captura os dados do formulário
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_STRING);


$result_usuario = "INSERT INTO login (nome, email, senha) VALUES ('$nome', '$email', '$senha')";
$resultado_usuario = mysqli_query($conn, $result_usuario);

// Verifica se o ID foi gerado com sucesso
if(mysqli_insert_id($conn)){
    $_SESSION['msg'] = "<p style='color:green;'>Usuário cadastrado com sucesso</p>";
    header("Location: index.php");
} else {
    $_SESSION['msg'] = "<p style='color:red;'>Usuário não foi cadastrado com sucesso: " . mysqli_error($conn) . "</p>";
    header("Location: index.php");
}
