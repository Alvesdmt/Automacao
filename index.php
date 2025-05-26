<?php
require_once 'config/conexao.php';
require_once 'whatsapp.php';

class Cadastro {
    private $conn;
    private $nome;
    private $telefone;
    private $erro;
    private $whatsapp;

    public function __construct() {
        $this->conn = Conexao::getInstance()->getConnection();
        $this->whatsapp = new WhatsAppSender();
    }

    public function validarDados($nome, $telefone) {
        $this->nome = trim($nome);
        $this->telefone = preg_replace('/[^0-9]/', '', $telefone);

        if (empty($this->nome)) {
            $this->erro = "O nome é obrigatório";
            return false;
        }

        if (strlen($this->nome) < 3) {
            $this->erro = "O nome deve ter pelo menos 3 caracteres";
            return false;
        }

        if (empty($this->telefone)) {
            $this->erro = "O telefone é obrigatório";
            return false;
        }

        if (strlen($this->telefone) < 10 || strlen($this->telefone) > 11) {
            $this->erro = "Telefone inválido";
            return false;
        }

        return true;
    }

    public function salvar() {
        try {
            $stmt = $this->conn->prepare("INSERT INTO cadastros (nome, telefone) VALUES (:nome, :telefone)");
            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':telefone', $this->telefone);
            $stmt->execute();

            // Envia mensagem via WhatsApp
            $this->whatsapp->enviarMensagem($this->telefone, $this->nome);
            
            return true;
        } catch(PDOException $e) {
            $this->erro = "Erro ao salvar: " . $e->getMessage();
            return false;
        }
    }

    public function getErro() {
        return $this->erro;
    }
}

$resposta = array('status' => 'erro', 'mensagem' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cadastro = new Cadastro();
    
    if ($cadastro->validarDados($_POST['nome'], $_POST['telefone'])) {
        if ($cadastro->salvar()) {
            $resposta['status'] = 'sucesso';
            $resposta['mensagem'] = 'Cadastro realizado com sucesso!';
        } else {
            $resposta['mensagem'] = $cadastro->getErro();
        }
    } else {
        $resposta['mensagem'] = $cadastro->getErro();
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($resposta);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Promoções do Restaurante</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #000;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 2;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        h1 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 16px;
            color: #fff;
            transition: all 0.3s ease;
        }

        input:focus {
            border-color: #00ff88;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
            outline: none;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(45deg, #00ff88, #00b3ff);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }

        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success {
            background: rgba(0, 255, 136, 0.2);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .error {
            background: rgba(255, 0, 0, 0.2);
            color: #ff4444;
            border: 1px solid rgba(255, 0, 0, 0.3);
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #fff;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.5;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-animation" id="backgroundAnimation"></div>
    <div class="container">
        <h1>Cadastre-se para Promoções</h1>
        <form id="cadastroForm" method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" required placeholder="Digite seu nome completo">
            </div>
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" required placeholder="(00) 00000-0000">
            </div>
            <button type="submit">Cadastrar</button>
        </form>
        <div id="message" class="message"></div>
    </div>

    <script>
        // Efeito de partículas no fundo
        function createParticles() {
            const container = document.getElementById('backgroundAnimation');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Posição aleatória
                particle.style.left = Math.random() * 100 + 'vw';
                particle.style.top = Math.random() * 100 + 'vh';
                
                // Tamanho aleatório
                const size = Math.random() * 3 + 1;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Animação
                particle.style.animation = `float ${Math.random() * 10 + 5}s linear infinite`;
                
                container.appendChild(particle);
            }
        }

        // Animação de flutuação
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0% { transform: translateY(0) translateX(0); }
                50% { transform: translateY(-20px) translateX(10px); }
                100% { transform: translateY(0) translateX(0); }
            }
        `;
        document.head.appendChild(style);

        // Inicializar partículas
        createParticles();

        // Efeito de digitação no título
        const title = document.querySelector('h1');
        const originalText = title.textContent;
        title.textContent = '';
        let i = 0;

        function typeWriter() {
            if (i < originalText.length) {
                title.textContent += originalText.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        }

        typeWriter();

        // Form submission
        document.getElementById('cadastroForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('index.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                
                if(data.status === 'sucesso') {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.mensagem;
                    document.getElementById('cadastroForm').reset();
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = data.mensagem;
                }
            })
            .catch(error => {
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Erro ao realizar cadastro. Tente novamente.';
            });
        });

        // Máscara para o telefone com animação
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
                e.target.value = value;
            }
        });

        // Efeito de hover nos inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
