# Sistema Administrativo com Switch Case

Exemplo didático em PHP que apresenta um menu administrativo. Cada botão envia uma letra por `POST` para `processa.php`, que usa `switch case` para mostrar a mensagem correspondente.

## Estrutura

```text
ProjetoSwitch/
├── index.php       # interface e formulário do menu
├── processa.php    # processamento com switch case
└── style.css       # layout responsivo e estilos
```

## Como executar

Com o PHP instalado, dentro da pasta `ProjetoSwitch`, execute:

```bash
php -S localhost:8000
```

Abra `http://localhost:8000` no navegador.

## Opções tratadas

| Valor POST | Módulo |
|---|---|
| A | Cadastro de Clientes |
| B | Cadastro de Produtos |
| C | Relatório de Vendas |
| D | Controle de Estoque |
| E | Configurações |
| F | Sair do Sistema |
