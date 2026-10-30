<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Abajur Interativo com Fundo Animado</title>
    <style>
        body {
            width: 100vw;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            color: white;
            background: linear-gradient(-45deg, #111113, #1a1525, #0d1b2a, #1b1124);
            background-size: 400% 400%;
            animation: gradienteAnimado 15s ease infinite;
            transition: background 0.5s;
        }

        body.aceso {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
        }

        @keyframes gradienteAnimado {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .sala {
            display: flex;
            align-items: center;
            gap: 60px;
            background: rgba(0, 0, 0, 0.4);
            padding: 40px;
            border-radius: 15px;
            backdrop-filter: blur(5px);
        }

        .abajur {
            cursor: pointer;
            text-align: center;
        }

        .cupula {
            width: 80px;
            height: 60px;
            background-color: #444;
            border-radius: 20px 20px 0 0;
            transition: background 0.5s, box-shadow 0.5s;
        }

        .base {
            width: 10px;
            height: 80px;
            background-color: #666;
            margin: 0 auto;
        }

        .abajur.aceso .cupula {
            background-color: #ffdf00;
            box-shadow: 0px -10px 40px #ffdf00, 0px 0px 30px #ffdf00;
        }

        #formulario-container {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s;
            background: rgba(40, 40, 40, 0.95);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(255, 223, 0, 0.15);
        }

        #formulario-container.visivel {
            opacity: 1;
            visibility: visible;
        }

        .pe input[type="text"], 
        .pe input[type="password"] {
            padding: 6px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #555;
            background-color: #fff;
            color: #000;
            width: 200px;
        }
        #in {
            padding: 8px 15px;
            cursor: pointer;
            background-color: #ffdf00;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            color: #111;
        }
    </style>
</head>
<body>

<div class="sala">
<?php
		if(isset($_SESSION['msg'])){
			echo $_SESSION['msg'];
			unset($_SESSION['msg']);
		}
		?>
    
    <div id="abajur" class="abajur" onclick="alternarLuz()">
        <div class="cupula"></div>
        <div class="base"></div>
    </div>

    <div id="formulario-container">
        <form name="form1" action="processa.php" method="post" class="pe"> 
            Nome: <input name="nome" type="text"><br /><br />
            Email: <input name="email" type="text"><br /><br />
            Senha: <input name="senha" type="password"><br /><br />
            Repetir Senha: <input name="rep_senha" type="password"> <br /><br /> 
            <input type="submit" onclick="return validar()" id="in" value="Cadastrar"> 
        </form>
    </div>

</div>

<script language="javascript" type="text/javascript">
    function alternarLuz() {
        const abajur = document.getElementById('abajur');
        const formulario = document.getElementById('formulario-container');
        const corpo = document.body;

        abajur.classList.toggle('aceso');
        formulario.classList.toggle('visivel');
        corpo.classList.toggle('aceso');
    }

    function validar() {
        var nome = form1.nome.value;
        var email = form1.email.value;
        var senha = form1.senha.value;
        var rep_senha = form1.rep_senha.value;

        if (nome == "") {
            alert('Preencha o campo com seu nome');
            form1.nome.focus();
            return false;
        }   
        if (nome.length < 5) {
            alert('Digite seu nome completo');
            form1.nome.focus();
            return false;
        } 
        if (senha != rep_senha) {
            alert('Senhas diferentes');
            form1.senha.focus();
            return false;
        }
        return true;
    }
</script>

</body>
</html>
