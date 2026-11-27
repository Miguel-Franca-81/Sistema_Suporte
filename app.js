(function () {
    // 1. Gerenciador de Tema (Modo Escuro / Modo Claro)
    var themeBtn = document.getElementById('theme-toggle');
    var currentTheme = localStorage.getItem('nexus_theme') || 'dark';

    if (currentTheme === 'light') {
        document.body.classList.add('theme-light');
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            document.body.classList.toggle('theme-light');
            var isLight = document.body.classList.contains('theme-light');
            localStorage.setItem('nexus_theme', isLight ? 'light' : 'dark');
            if (window.lucide) lucide.createIcons();
        });
    }

    // 2. Funcionalidade de Expandir / Minimizar Linhas de Chamados
    document.querySelectorAll('.btn-expandir').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var targetId = btn.dataset.target;
            var targetRow = document.getElementById(targetId);
            if (targetRow) {
                targetRow.classList.toggle('oculta');
                btn.classList.toggle('ativo');
            }
        });
    });

    // Permitir clicar na linha de resumo inteira para expandir
    document.querySelectorAll('.linha-resumo').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a') || e.target.closest('button')) return;
            var btn = row.querySelector('.btn-expandir');
            if (btn) btn.click();
        });
    });

    // 3. Validação de Formulários no Frontend
    var regras = {
        nome: function (v) {
            v = v.trim();
            if (v === '') return 'Informe seu nome completo.';
            if (v.length < 5 || v.split(/\s+/).length < 2) return 'Digite nome e sobrenome.';
            return '';
        },
        email: function (v) {
            v = v.trim();
            if (v === '') return 'Informe seu e-mail.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Digite um e-mail válido.';
            return '';
        },
        senha: function (v) {
            if (v === '') return 'Crie uma senha.';
            if (v.length < 8) return 'A senha deve ter no mínimo 8 caracteres.';
            return '';
        },
        senha_login: function (v) {
            return v === '' ? 'Informe sua senha.' : '';
        },
        rep_senha: function (v, form) {
            if (v === '') return 'Confirme a senha.';
            if (v !== form.elements.senha.value) return 'As senhas não coincidem.';
            return '';
        },
        telefone: function (v) {
            var digitos = v.replace(/\D/g, '');
            if (digitos === '') return 'Informe um telefone para contato.';
            if (digitos.length < 8 || digitos.length > 11) return 'Digite um telefone/ramal válido.';
            return '';
        },
        obrigatorio: function (v) {
            return v.trim() === '' ? 'Preencha este campo.' : '';
        },
        descricao: function (v) {
            v = v.trim();
            if (v === '') return 'Descreva o problema.';
            if (v.length < 10) return 'Descreva com mais detalhes (mínimo de 10 caracteres).';
            return '';
        }
    };

    document.querySelectorAll('form[data-validar]').forEach(function (form) {
        var campos = Array.prototype.slice.call(form.querySelectorAll('[data-regra]'));

        function validar(campo) {
            var mensagem = regras[campo.dataset.regra](campo.value, form);
            var alvo = document.getElementById('erro-' + campo.id);
            if (alvo) alvo.textContent = mensagem;
            campo.setAttribute('aria-invalid', mensagem ? 'true' : 'false');
            return mensagem === '';
        }

        campos.forEach(function (campo) {
            campo.addEventListener('blur', function () { validar(campo); });
        });

        form.addEventListener('submit', function (evento) {
            var primeiroInvalido = null;
            campos.forEach(function (campo) {
                if (!validar(campo) && !primeiroInvalido) primeiroInvalido = campo;
            });
            if (primeiroInvalido) {
                evento.preventDefault();
                primeiroInvalido.focus();
                return;
            }
            var botao = form.querySelector('button[type="submit"]');
            if (botao) {
                botao.dataset.texto = botao.innerHTML;
                botao.disabled = true;
                botao.innerHTML = 'PROCESSANDO...';
            }
        });
    });

    document.querySelectorAll('.mostrar').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var campo = document.getElementById(botao.dataset.alvo);
            var visivel = campo.type === 'text';
            campo.type = visivel ? 'password' : 'text';
        });
    });
})();
