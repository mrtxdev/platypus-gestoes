# PLATYPUS
## Documentação Técnica do Projeto

**Sistema de gestão para restaurante com cardápio digital e gerenciamento operacional**

| Controle do documento | Informação |
|---|---|
| Identificação | DOC-PLT-001 |
| Versão | 1.0 |
| Data | 04/11/2026 |
| Idioma | Português brasileiro |
| Finalidade | Estudo, organização da equipe e apoio ao mini TCC |
| Situação | Documento de referência baseado nos trechos e decisões técnicas compartilhados pela equipe |

> **Nota sobre o padrão documental:** este material usa uma organização formal inspirada em documentação técnica e em práticas de controle de documentos. Isso não significa que o projeto ou este documento possuam certificação ou conformidade ISO. Os requisitos de uma norma específica só podem ser declarados após uma avaliação própria.

---

# Sumário

1. [Apresentação](#1-apresentação)
2. [Objetivo e escopo](#2-objetivo-e-escopo)
3. [Visão geral da arquitetura](#3-visão-geral-da-arquitetura)
4. [Estrutura de diretórios](#4-estrutura-de-diretórios)
5. [Padrões de desenvolvimento](#5-padrões-de-desenvolvimento)
6. [Fluxo de uma requisição](#6-fluxo-de-uma-requisição)
7. [Router, rotas e permissões](#7-router-rotas-e-permissões)
8. [Banco de dados](#8-banco-de-dados)
9. [Conexão e configuração do ambiente](#9-conexão-e-configuração-do-ambiente)
10. [Models](#10-models)
11. [Controllers](#11-controllers)
12. [Views](#12-views)
13. [Helpers e funções reutilizáveis](#13-helpers-e-funções-reutilizáveis)
14. [Identificadores, IDs e tokens](#14-identificadores-ids-e-tokens)
15. [Integração do QR Code com as mesas](#15-integração-do-qr-code-com-as-mesas)
16. [Cardápio do cliente](#16-cardápio-do-cliente)
17. [Autenticação e autorização](#17-autenticação-e-autorização)
18. [Uploads de imagens](#18-uploads-de-imagens)
19. [Segurança e boas práticas](#19-segurança-e-boas-práticas)
20. [Como implementar uma nova funcionalidade](#20-como-implementar-uma-nova-funcionalidade)
21. [Ambiente de desenvolvimento e comandos úteis](#21-ambiente-de-desenvolvimento-e-comandos-úteis)
22. [Testes e critérios de verificação](#22-testes-e-critérios-de-verificação)
23. [Estado funcional e próximos passos](#23-estado-funcional-e-próximos-passos)
24. [Pontos de atenção e decisões pendentes](#24-pontos-de-atenção-e-decisões-pendentes)
25. [Glossário](#25-glossário)
26. [Orientação para apresentação no mini TCC](#26-orientação-para-apresentação-no-mini-tcc)
27. [Histórico de revisões](#27-histórico-de-revisões)

---

# 1. Apresentação

O **Platypus** é um sistema de gestão para restaurantes. A proposta é centralizar atividades de administração e operação, incluindo gerenciamento de usuários, cardápio, categorias, mesas, QR Codes, pedidos, cozinha, estoque, caixa, pagamentos e relatórios.

O projeto utiliza PHP no servidor, MariaDB como banco de dados, HTML para estrutura das páginas e CSS e JavaScript para apresentação e interações no navegador. A aplicação segue uma organização inspirada no padrão MVC, com um arquivo central de roteamento.

A aplicação foi desenvolvida com uma separação importante entre nomes internos e textos apresentados ao usuário:

- **Inglês:** nomes de funções, variáveis, propriedades, tabelas, colunas, índices, valores internos de status e identificadores técnicos.
- **Português:** títulos, mensagens, rótulos, botões, instruções e demais textos apresentados aos usuários do restaurante.

Essa separação não é uma exigência do PHP. É uma convenção adotada pelo projeto para padronizar o código e manter a interface adequada ao público brasileiro.

## 1.1 Público do sistema

O sistema considera três perfis internos principais:

| Perfil | Valor interno | Responsabilidade prevista |
|---|---|---|
| Administrador | `ADMIN` | Gerenciar configurações, usuários, cardápio, mesas e informações administrativas |
| Cozinha | `KITCHEN` | Acompanhar e atualizar o andamento dos pedidos e consultar funções operacionais da cozinha |
| Atendente | `ATTENDANT` | Acompanhar pedidos prontos, chamados e mesas conforme as permissões configuradas |
| Cliente | Sem conta interna obrigatória no fluxo atual | Consultar o cardápio a partir do QR Code de uma mesa |

O cliente do cardápio digital não é tratado como um usuário cadastrado na tabela `users`. No fluxo atual, a mesa é identificada pelo token presente no QR Code.

---

# 2. Objetivo e escopo

## 2.1 Objetivo geral

Organizar em uma aplicação única informações e rotinas necessárias à operação de um restaurante, reduzindo controles dispersos e permitindo que diferentes perfis acessem funcionalidades compatíveis com suas responsabilidades.

## 2.2 Objetivos específicos

- Cadastrar e autenticar funcionários.
- Controlar permissões por perfil.
- Cadastrar categorias e produtos do cardápio.
- Armazenar imagens dos produtos.
- Cadastrar mesas e gerar QR Codes individuais.
- Identificar a mesa que acessa o cardápio digital.
- Consultar produtos disponíveis.
- Preparar a estrutura de dados para pedidos, cozinha, estoque, caixa e pagamentos.
- Disponibilizar relatórios administrativos a partir dos dados operacionais.

## 2.3 Escopo confirmado pelo código compartilhado

Os trechos apresentados e trabalhados durante o desenvolvimento demonstram implementação ou estrutura de implementação para:

- Router central com cadastro de rotas e autorização por perfil.
- Autenticação de usuários e sessões.
- Cadastro e consulta de categorias.
- Cadastro, consulta, edição e exclusão de produtos.
- Upload e remoção de imagens de produtos.
- Cadastro, consulta e exclusão de mesas.
- Geração de QR Codes e identificação de mesa por token.
- Página pública de cardápio que apresenta produtos disponíveis.
- Estrutura de banco para pedidos, itens, histórico de status, ingredientes, receitas, movimentações, caixa e pagamentos.

## 2.4 Limites conhecidos

Ter uma tabela no banco ou uma rota cadastrada não significa, por si só, que todo o fluxo funcional esteja concluído. Por exemplo, a página de cardápio do cliente compartilhada apresenta produtos e identifica a mesa, mas informa que o pedido deve ser solicitado à equipe. Portanto, **o envio de pedidos pelo cliente não deve ser apresentado como concluído apenas com base nessa página**.

O fluxo completo de pedidos, cozinha, pagamentos, caixa, estoque e relatórios deve ser avaliado funcionalidade por funcionalidade antes de ser declarado pronto no mini TCC.

---

# 3. Visão geral da arquitetura

## 3.1 Organização em camadas

O projeto é organizado em partes com responsabilidades diferentes. A estrutura é inspirada em MVC, mas utiliza PHP procedural e funções globais em vários controllers. Portanto, é mais preciso descrevê-la como uma **arquitetura PHP organizada por responsabilidades, com Router central e separação inspirada em MVC**, em vez de afirmar que é uma implementação MVC estrita.

| Camada | Responsabilidade | Exemplo |
|---|---|---|
| Router | Interpretar a rota, verificar acesso e direcionar ações e páginas | `router.php` |
| Controller | Coordenar uma funcionalidade, validar dados e chamar o banco ou serviços | `product.controller.php` |
| Model | Representar a estrutura de dados em objetos PHP | `product.model.php` |
| View | Apresentar informações e formulários HTML | `menu.admin.php` |
| Configuração | Disponibilizar conexão, ambiente e funções comuns | `config/database.php` |
| Helper | Reunir operações genéricas reutilizáveis | `config/helpers.php` |
| Assets | Armazenar CSS, JavaScript, ícones e imagens públicas | `view/public/assets/` |
| Banco de dados | Persistir informações e relacionamentos | MariaDB |

## 3.2 Responsabilidade de cada parte

### Router

É a porta de entrada das páginas. O router lê os parâmetros da requisição, identifica qual rota foi solicitada, verifica autenticação e autorização, executa handlers de operações POST e carrega a View correspondente.

### Controller

Coordena a lógica de uma funcionalidade. Um controller pode:

1. Receber dados de `$_GET`, `$_POST` ou `$_FILES`.
2. Validar os dados recebidos.
3. Verificar sessão, permissão ou token CSRF quando necessário.
4. Solicitar uma conexão ao banco.
5. Executar consultas preparadas.
6. Preparar os dados para uma View ou redirecionar o usuário.
7. Registrar detalhes técnicos no log quando ocorrer um erro.

### Model

Representa uma entidade ou conjunto de dados. Os models compartilhados no projeto incluem `Category`, `Product` e `User`.

### View

É responsável pela interface. Deve receber dados preparados e apresentá-los. Regras importantes de acesso, validação e persistência não devem ser implementadas diretamente no HTML.

### Helper

Contém funções auxiliares genéricas que podem ser reutilizadas em diferentes partes do sistema. Um helper deve resolver uma tarefa pequena e transversal, e não concentrar regras completas de negócio.

---

# 4. Estrutura de diretórios

A estrutura abaixo representa a organização conhecida a partir dos arquivos e caminhos compartilhados. Alguns arquivos e pastas podem variar conforme os recursos que ainda estiverem sendo desenvolvidos.

```text
platypus_gestoes/
│
├── config/
│   ├── database.php
│   └── helpers.php
│
├── controller/
│   ├── user.controller.php
│   ├── category.controller.php
│   ├── product.controller.php
│   ├── generate.qrcode.controller.php
│   └── client.menu.controller.php
│
├── model/
│   ├── user.model.php
│   ├── category.model.php
│   └── product.model.php
│
├── database/
│   └── schema.sql
│
├── view/
│   ├── includes/
│   │   ├── header.php
│   │   └── footer.php
│   │
│   └── public/
│       ├── admin/
│       │   ├── login.admin.php
│       │   ├── dashboard.admin.php
│       │   ├── new.user.admin.php
│       │   ├── generate.qrcode.admin.php
│       │   ├── menu.admin.php
│       │   ├── add.menu.admin.php
│       │   ├── edit.menu.admin.php
│       │   ├── add.category.admin.php
│       │   ├── mesas_ativas.php
│       │   ├── relatorios.php
│       │   ├── monitor_cozinha.php
│       │   ├── estoque_rapido.php
│       │   ├── pedidos_prontos.php
│       │   └── chamados_mesa.php
│       │
│       ├── client/
│       │   └── menu.client.php
│       │
│       └── assets/
│           ├── css/
│           │   └── style.css
│           ├── js/
│           │   └── qrcode.js
│           └── images/
│               ├── icons/
│               └── products/
│
├── .env
├── .htaccess                  (opcional, para URLs amigáveis)
└── router.php
```

## 4.1 Observações sobre os diretórios

- `config/`: contém configurações compartilhadas, como conexão com o banco e helpers.
- `controller/`: contém a lógica das funcionalidades.
- `model/`: contém classes que representam entidades utilizadas pelo sistema.
- `database/`: contém o schema SQL e, caso adotado, scripts de migração e dados iniciais.
- `view/includes/`: contém trechos visuais compartilhados, como cabeçalho e rodapé.
- `view/public/admin/`: contém páginas da área administrativa e operacional.
- `view/public/client/`: contém a interface pública do cliente.
- `view/public/assets/`: contém arquivos estáticos e imagens servidas pelo Apache.
- `.env`: contém configurações locais e segredos que não devem ser publicados.
- `router.php`: centraliza o roteamento da aplicação.

**Atenção:** se `client.menu.controller.php` ou `helpers.php` ainda não tiverem sido criados fisicamente no projeto, devem ser adicionados nos caminhos mostrados antes de a View tentar incluí-los. Um `require_once` não cria o arquivo; apenas tenta carregá-lo.

---

# 5. Padrões de desenvolvimento

## 5.1 Idioma

O projeto adota a seguinte convenção:

| Elemento | Idioma recomendado | Exemplo |
|---|---|---|
| Funções PHP | Inglês | `registerProduct()` |
| Variáveis PHP | Inglês | `$productName` |
| Propriedades | Inglês | `$isAvailable` |
| Classes | Inglês | `Product` |
| Tabelas e colunas | Inglês | `dining_tables`, `created_at` |
| Valores internos | Inglês | `AVAILABLE`, `PENDING` |
| Mensagens ao usuário | Português | `Produto cadastrado com sucesso!` |
| Rótulos e botões | Português | `Adicionar Produto` |
| Comentários técnicos | Inglês, conforme padrão adotado | `// Product registration` |

## 5.2 Nomenclatura

O projeto utiliza nomes descritivos em camelCase para funções e variáveis:

```php
getDatabaseConnection();
registerProduct();
$tableNumber = '12';
$isAvailable = true;
```

Tabelas e colunas utilizam `snake_case`:

```sql
dining_tables
table_number
created_at
```

## 5.3 Funções globais

Os controllers atuais declaram funções globais. Isso funciona, mas exige cuidado com nomes repetidos. Nomes genéricos como `add()` e `all()` podem colidir com funções de outros arquivos se dois controllers forem incluídos na mesma requisição.

Como evolução, recomenda-se adotar nomes específicos (`registerCategory()`, `getAllCategories()`) ou namespaces/classes para evitar colisões. Essa mudança deve ser feita em conjunto com os pontos que chamam as funções, especialmente o router.

## 5.4 Consultas SQL

O padrão recomendado para novas consultas é utilizar `prepare()` e `execute()`, inclusive em consultas estáticas, por consistência do projeto:

```php
$statement = $database->prepare(
    'SELECT id, name FROM products WHERE is_available = :isAvailable'
);

$statement->execute([
    ':isAvailable' => 1
]);

$products = $statement->fetchAll(PDO::FETCH_ASSOC);
```

Parâmetros preparados devem ser usados para valores. Nomes de tabelas, colunas e trechos de ordenação não podem ser substituídos por parâmetros PDO; quando forem dinâmicos, precisam ser escolhidos a partir de uma lista permitida no próprio código.

## 5.5 Tratamento de erros

- Mensagens técnicas devem ser registradas em `error_log()`.
- Mensagens apresentadas ao cliente devem ser claras e não revelar detalhes internos.
- Controllers devem tratar falhas esperadas e manter o fluxo de resposta consistente.
- Em produção, `display_errors` deve ficar desativado e os erros devem ser registrados em log.

---

# 6. Fluxo de uma requisição

## 6.1 Fluxo geral

```text
Navegador
   │
   ▼
router.php
   │
   ├── Lê pagina e action
   │
   ├── Procura a rota em $routes
   │
   ├── Verifica autenticação
   │
   ├── Verifica perfil autorizado
   │
   ├── Se for uma ação POST:
   │      ├── Inclui o controller
   │      ├── Chama a função responsável
   │      └── Encerra ou redireciona
   │
   └── Carrega o arquivo da View
          │
          ▼
       Resposta HTML
```

## 6.2 Requisição GET

Uma requisição GET geralmente solicita uma página ou consulta dados para exibição.

Exemplo administrativo:

```text
/router.php?pagina=cardapio/admin
```

O router localiza a chave `cardapio/admin` no array `$routes`, verifica o perfil `ADMIN` e carrega `menu.admin.php`.

## 6.3 Requisição POST

Uma requisição POST envia dados para processamento, como cadastro de produto.

Exemplo:

```text
/router.php?pagina=cardapio/admin/adicionar&action=register-product
```

O formulário envia os campos ao router. O router identifica página, ação e método HTTP, inclui `product.controller.php`, chama `registerProduct()` e encerra a execução antes de carregar a View.

## 6.4 Por que o router chama a função do controller?

`require_once` apenas carrega o arquivo e disponibiliza suas declarações. Ele não executa automaticamente uma função declarada nele.

Por isso, este padrão é necessário:

```php
require_once __DIR__ . '/controller/category.controller.php';
add();
exit;
```

Omitir `add()` faria o controller ser incluído, mas o cadastro não seria realizado.

---

# 7. Router, rotas e permissões

## 7.1 Parâmetros principais

O router utiliza dois parâmetros da URL:

- `pagina`: identifica a página solicitada.
- `action`: identifica uma operação de controller, quando aplicável.

Exemplo:

```text
router.php?pagina=gerar-qrcode&action=generate-qr-code
```

O valor de `pagina` identifica a tela ou contexto; `action` identifica a operação POST.

## 7.2 Estrutura de uma rota

Cada entrada em `$routes` possui:

| Campo | Significado |
|---|---|
| `file` | Caminho do arquivo PHP que será carregado |
| `public` | Indica se a rota dispensa autenticação |
| `roles` | Perfil ou lista de perfis autorizados |
| `title` | Título descritivo da página |

Exemplo conceitual:

```php
'gerar-qrcode' => [
    'file' => 'view/public/admin/generate.qrcode.admin.php',
    'public' => false,
    'roles' => 'ADMIN',
    'title' => 'Gerar QR-Code'
],
```

## 7.3 Rotas conhecidas

A tabela abaixo representa a organização compartilhada mais recente. Deve ser comparada com o `router.php` existente antes de ser considerada uma listagem definitiva.

| Rota (`pagina`) | View | Acesso esperado |
|---|---|---|
| `login` | `admin/login.admin.php` | Pública |
| `dashboard` | `admin/dashboard.admin.php` | `ADMIN`, `KITCHEN`, `ATTENDANT` |
| `novo-usuario` | `admin/new.user.admin.php` | `ADMIN` |
| `gerar-qrcode` | `admin/generate.qrcode.admin.php` | `ADMIN` |
| `cardapio` | `client/menu.client.php` | Pública, com token de mesa |
| `cardapio/admin` | `admin/menu.admin.php` | `ADMIN` |
| `cardapio/admin/adicionar` | `admin/add.menu.admin.php` | `ADMIN` |
| `categorias/adicionar` | `admin/add.category.admin.php` | `ADMIN` |
| `cardapio/admin/editar` | `admin/edit.menu.admin.php` | `ADMIN` |
| `mesas-ativas` | `admin/mesas_ativas.php` | `ADMIN`, `ATTENDANT` |
| `relatorios` | `admin/relatorios.php` | `ADMIN` |
| `monitor-cozinha` | `admin/monitor_cozinha.php` | `KITCHEN` |
| `estoque-rapido` | `admin/estoque_rapido.php` | `KITCHEN` |
| `pedidos-prontos` | `admin/pedidos_prontos.php` | `ATTENDANT` |
| `chamados-mesa` | `admin/chamados_mesa.php` | `ATTENDANT` |

## 7.4 Convenção entre rota, formulário e controller

Os três pontos precisam usar exatamente o mesmo contrato:

1. A rota registrada no `$routes`.
2. A condição do handler no router.
3. O `action` e `pagina` do formulário.

Exemplo de cadastro de produto:

| Local | Valor esperado |
|---|---|
| Rota da View | `cardapio/admin/adicionar` |
| Formulário | `pagina=cardapio/admin/adicionar&action=register-product` |
| Handler | `$page === 'cardapio/admin/adicionar'` e `$action === 'register-product'` |
| Função | `registerProduct()` |

Se um deles permanecer com um nome antigo, o handler não será executado.

## 7.5 Menus por perfil

`$menusByRole` define os links apresentados para cada perfil. A rota deve estar registrada em `$routes` e o perfil precisa ter autorização compatível.

A variável compartilhada com o cabeçalho é `$menuLinks`, contendo elementos com `label` e `url`. Router e header precisam usar os mesmos nomes e o mesmo formato.

## 7.6 URLs amigáveis

O router apresentado utiliza inicialmente URLs com query string, por exemplo:

```text
/router.php?pagina=cardapio/admin
```

URLs como `/cardapio/` exigem regras adicionais do Apache, normalmente por `.htaccess` com `mod_rewrite` habilitado e `AllowOverride` configurado. A existência de uma rota PHP não cria automaticamente uma URL amigável.

Uma regra de reescrita também deve preservar os parâmetros necessários, como `id`, `action` e `mesa`.

---

# 8. Banco de dados

## 8.1 Banco utilizado

Nome do banco conhecido no projeto:

```text
restaurant_management
```

A aplicação utiliza MariaDB por meio do PDO.

## 8.2 Tabelas identificadas

O schema compartilhado contém as seguintes 12 tabelas:

1. `users`
2. `dining_tables`
3. `categories`
4. `products`
5. `orders`
6. `order_items`
7. `order_status_history`
8. `ingredients`
9. `recipes`
10. `inventory_movements`
11. `cash_registers`
12. `payments`

## 8.3 Dicionário de dados resumido

### `users`

Armazena os usuários internos do sistema.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador interno |
| `name` | Nome do usuário |
| `email` | E-mail de acesso |
| `password_hash` | Hash da senha |
| `role` | Perfil (`ADMIN`, `KITCHEN`, `ATTENDANT`) |
| `is_active` | Indica se a conta está ativa |
| `created_at` | Data de criação |

### `dining_tables`

Armazena as mesas do restaurante e os tokens dos QR Codes.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador interno da mesa |
| `table_number` | Número ou identificação apresentada |
| `qr_token` | Token público de identificação |
| `status` | Estado da mesa |
| `created_at` | Data de criação |

Valores de status conhecidos: `AVAILABLE`, `OCCUPIED`, `INACTIVE`.

### `categories`

Armazena categorias de produtos.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador interno |
| `name` | Nome da categoria |
| `description` | Descrição |
| `is_active` | Indica se a categoria está ativa |

### `products`

Armazena os produtos exibidos no cardápio.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador interno |
| `category_id` | Categoria associada, quando informada |
| `name` | Nome do produto |
| `description` | Descrição do produto |
| `price` | Preço de venda |
| `is_available` | Disponibilidade para venda |
| `image` | Nome do arquivo de imagem |
| `created_at` | Data de criação |

### `orders`

Armazena pedidos associados a uma mesa e, quando aplicável, a um usuário interno.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador do pedido |
| `table_id` | Mesa associada |
| `user_id` | Usuário interno associado, quando aplicável |
| `status` | Estado do pedido |
| `total_amount` | Valor total |
| `notes` | Observações |
| `created_at` | Data de criação |
| `updated_at` | Data de atualização |

Valores de status conhecidos: `PENDING`, `PREPARING`, `READY`, `DELIVERED`, `CANCELLED`.

### `order_items`

Armazena os itens de cada pedido.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador do item |
| `order_id` | Pedido ao qual pertence |
| `product_id` | Produto de origem |
| `product_name` | Nome registrado no momento do pedido |
| `quantity` | Quantidade |
| `unit_price` | Preço unitário registrado |
| `notes` | Observações do item |

A gravação de `product_name` e `unit_price` no item permite preservar informações da venda mesmo que o cadastro atual do produto seja alterado posteriormente.

### `order_status_history`

Armazena o histórico de mudanças de status de pedidos.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador do registro |
| `order_id` | Pedido relacionado |
| `user_id` | Usuário que realizou a alteração, quando aplicável |
| `previous_status` | Status anterior |
| `new_status` | Novo status |
| `created_at` | Data da alteração |
| `notes` | Observações |

### `ingredients`

Armazena ingredientes e quantidades de estoque.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador do ingrediente |
| `name` | Nome |
| `unit` | Unidade de medida |
| `current_quantity` | Quantidade atual |
| `minimum_quantity` | Quantidade mínima |
| `is_active` | Estado do ingrediente |

### `recipes`

Relaciona produtos aos ingredientes utilizados e suas quantidades.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador |
| `product_id` | Produto relacionado |
| `ingredient_id` | Ingrediente relacionado |
| `quantity` | Quantidade utilizada |

### `inventory_movements`

Registra movimentações de ingredientes.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador |
| `ingredient_id` | Ingrediente movimentado |
| `user_id` | Usuário responsável, quando aplicável |
| `type` | Tipo de movimentação |
| `quantity` | Quantidade movimentada |
| `reason` | Motivo |
| `created_at` | Data da movimentação |

Valores de tipo conhecidos: `IN`, `OUT`, `ADJUSTMENT`.

### `cash_registers`

Armazena sessões de caixa.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador do caixa |
| `opened_by_user_id` | Usuário que abriu |
| `closed_by_user_id` | Usuário que fechou, quando aplicável |
| `opening_amount` | Valor inicial |
| `closing_amount` | Valor informado no fechamento |
| `opened_at` | Data de abertura |
| `closed_at` | Data de fechamento |
| `status` | Estado do caixa |

Valores de status conhecidos: `OPEN`, `CLOSED`.

### `payments`

Armazena pagamentos relacionados a pedidos e caixas.

| Coluna | Finalidade |
|---|---|
| `id` | Identificador |
| `order_id` | Pedido pago |
| `cash_register_id` | Caixa associado |
| `payment_method` | Meio de pagamento |
| `amount` | Valor |
| `status` | Estado do pagamento |
| `paid_at` | Data do pagamento |

Meios de pagamento conhecidos: `PIX`, `CARD`, `CASH`, `OTHER`.

Estados conhecidos: `PENDING`, `CONFIRMED`, `REFUNDED`.

## 8.4 Relacionamentos conceituais

```text
categories 1 ───── N products
products   1 ───── N order_items
orders     1 ───── N order_items
orders     1 ───── N order_status_history
users      1 ───── N orders (quando associado)
users      1 ───── N order_status_history
users      1 ───── N inventory_movements
users      1 ───── N cash_registers (abertura/fechamento)
dining_tables 1 ── N orders
ingredients 1 ──── N recipes
products   1 ───── N recipes
ingredients 1 ──── N inventory_movements
orders     1 ───── N payments
cash_registers 1 ─ N payments
```

O desenho representa os relacionamentos conceituais descritos pelas colunas. A cardinalidade e a existência de todas as constraints devem ser confirmadas no `schema.sql`.

## 8.5 Por que usar IDs internos?

Os IDs são identificadores usados nos relacionamentos do banco. Por exemplo, um pedido guarda `table_id` para indicar a mesa à qual pertence.

A consulta dos pedidos de uma mesa pode usar o identificador interno:

```sql
SELECT id, status, total_amount, created_at
FROM orders
WHERE table_id = :tableId;
```

O valor `:tableId` deve ser obtido e validado no servidor a partir da mesa identificada, não aceito cegamente do navegador.

---

# 9. Conexão e configuração do ambiente

## 9.1 Arquivo de conexão

O arquivo `config/database.php` é responsável por carregar configurações do ambiente e fornecer a conexão PDO.

A função de conexão adotada pelo projeto é:

```php
$database = getDatabaseConnection();
```

Não utilizar nomes antigos, como `getDatabaseConfig()`, quando o arquivo atual disponibiliza `getDatabaseConnection()`.

## 9.2 Variáveis do `.env`

As variáveis utilizadas pelo projeto são:

```env
DB_HOST=localhost
DB_NAME=restaurant_management
DB_USER=usuario_do_banco
DB_PASS=senha_do_banco
APP_BASE_URL=http://192.168.3.32/platypus_gestoes
```

Os valores acima são exemplos de configuração. O usuário e a senha reais devem ser definidos conforme a instalação local. Não se deve incluir credenciais reais em documentação pública ou repositórios compartilhados.

`APP_BASE_URL` é utilizado para compor os links absolutos dos QR Codes. Em ambiente local, pode conter o IP da máquina na rede. Em produção, deve conter o domínio acessível pelos clientes, preferencialmente com HTTPS.

## 9.3 Carregamento do `.env`

A função `loadEnv()` do projeto exige o caminho do arquivo. Portanto, não deve ser chamada sem argumento.

Exemplo conceitual:

```php
loadEnv(__DIR__ . '/../.env');
```

O caminho exato depende de onde a chamada está sendo realizada. O ideal é centralizar o carregamento do `.env` em `config/database.php`, evitando que cada View precise carregá-lo novamente.

## 9.4 PDO

A conexão deve utilizar `PDO`, tratamento de exceções e charset `utf8mb4`. As consultas devem utilizar parâmetros para os valores recebidos.

---

# 10. Models

Os models conhecidos representam entidades do sistema por meio de propriedades PHP.

## 10.1 `Category`

Propriedades conhecidas:

- `id`
- `name`
- `description`
- `isActive`

## 10.2 `Product`

Propriedades conhecidas:

- `id`
- `categoryId`
- `name`
- `description`
- `price`
- `isAvailable`
- `image`
- `createdAt`

## 10.3 `User`

Propriedades conhecidas:

- `id`
- `name`
- `email`
- `password`
- `role`
- `isActive`

O controller de autenticação compartilhado utiliza arrays associativos do PDO para consultar usuários e trabalhar com `password_hash`. Portanto, não se deve presumir que o model `User` esteja sendo utilizado em todas as operações de autenticação.

## 10.4 Conversão de colunas para propriedades

Quando o código utiliza `PDO::FETCH_CLASS`, aliases SQL podem alinhar nomes `snake_case` do banco aos nomes camelCase do model:

```sql
SELECT
    category_id AS categoryId,
    is_available AS isAvailable
FROM products;
```

Assim, o objeto pode receber propriedades como `$product->categoryId` e `$product->isAvailable`.

---

# 11. Controllers

## 11.1 `user.controller.php`

Responsável pelas funções de autenticação e gerenciamento de sessão conhecidas.

Funções compartilhadas:

| Função | Responsabilidade |
|---|---|
| `emailExists()` | Verificar se um e-mail já está cadastrado |
| `loginUser()` | Validar credenciais e iniciar sessão autenticada |
| `logoutUser()` | Encerrar sessão e redirecionar |
| `registerUser()` | Cadastrar usuário e armazenar hash da senha |
| `isAuthenticated()` | Verificar se há usuário autenticado |
| `hasRole()` | Verificar se o perfil da sessão possui a permissão exigida |

A sessão utiliza chaves internas padronizadas, incluindo:

- `user_id`
- `user_name`
- `user_email`
- `user_role`
- `auth`

## 11.2 `category.controller.php`

Funções conhecidas:

| Função | Responsabilidade |
|---|---|
| `add()` | Processar o cadastro de uma categoria via POST |
| `all()` | Consultar categorias ativas |

O formulário utiliza `name` e `description`. O controller compartilhado grava `is_active = 1`. Caso o formulário ofereça opção para cadastrar uma categoria inativa, o controller precisa ser atualizado para validar e utilizar o campo `is_active` enviado.

Os nomes `add()` e `all()` são genéricos. Recomenda-se planejar nomes específicos ou namespaces para evitar colisões entre controllers.

## 11.3 `product.controller.php`

Funções conhecidas:

| Função | Responsabilidade |
|---|---|
| `redirectProduct()` | Redirecionar para a área de cardápio com mensagem |
| `getProductImageDirectory()` | Retornar a pasta de imagens |
| `removeProductImage()` | Remover imagem cujo nome passe pelas verificações permitidas |
| `saveProductImage()` | Validar e salvar imagem enviada |
| `getAllProducts()` | Consultar produtos |
| `getProductById()` | Consultar um produto pelo ID |
| `validateProductData()` | Validar dados do formulário |
| `registerProduct()` | Cadastrar produto |
| `updateProduct()` | Atualizar produto |
| `deleteProduct()` | Excluir produto |

O controller trabalha com os campos internos `name`, `description`, `category_id`, `price`, `is_available`, `image` e `id`.

A imagem é validada por tipo MIME e conteúdo, possui limite de tamanho no controller e recebe um nome aleatório. A pasta de destino conhecida é:

```text
view/public/assets/images/products/
```

## 11.4 `generate.qrcode.controller.php`

Funções conhecidas:

| Função | Responsabilidade |
|---|---|
| `redirectQr()` | Redirecionar para a página de QR Codes com mensagem |
| `validateQrCsrf()` | Comparar token CSRF da sessão com o token enviado |
| `generateQrCode()` | Validar e cadastrar uma mesa com token QR |
| `deleteQrTable()` | Validar e excluir uma mesa |
| `getQrTables()` | Consultar mesas cadastradas |

O controller não precisa desenhar a imagem QR. Essa responsabilidade é do JavaScript da página administrativa.

## 11.5 `client.menu.controller.php`

Responsável por preparar os dados da página pública:

- Receber o token `mesa`.
- Validar o formato.
- Consultar `dining_tables` pelo `qr_token`.
- Rejeitar token inexistente ou mesa inativa.
- Consultar produtos com `is_available = 1`.
- Preparar o endereço público das imagens válidas.
- Devolver uma estrutura com mesa, produtos e erro, quando houver.

A função compartilhada para essa responsabilidade é `getClientMenuData()`.

---

# 12. Views

## 12.1 Área administrativa

As Views administrativas apresentam dados e formulários para o administrador e os perfis operacionais autorizados.

Exemplos conhecidos:

- `login.admin.php`
- `dashboard.admin.php`
- `new.user.admin.php`
- `generate.qrcode.admin.php`
- `menu.admin.php`
- `add.menu.admin.php`
- `edit.menu.admin.php`
- `add.category.admin.php`

As páginas compartilhadas utilizam includes para cabeçalho e rodapé:

```php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/footer.php';
```

O nível de diretório deve ser ajustado conforme a localização da View.

## 12.2 `menu.admin.php`

Apresenta os produtos cadastrados, suas imagens, descrição, preço e disponibilidade. Também oferece links para adicionar produtos e categorias, além das ações de editar e excluir.

A View consulta o controller de produtos para obter a lista. As ações POST são direcionadas ao router.

## 12.3 `add.menu.admin.php`

Formulário de cadastro de produto. Os campos conhecidos são:

- `image`
- `name`
- `category_id`
- `description`
- `price`
- `is_available`

Como há upload de imagem, o formulário precisa utilizar `enctype="multipart/form-data"`.

## 12.4 `add.category.admin.php`

Formulário de categoria com nome e descrição. O código compartilhado também apresenta seletor de status; é necessário alinhar esse campo à lógica do controller, que na versão apresentada grava status ativo fixo.

## 12.5 `generate.qrcode.admin.php`

Apresenta o formulário para cadastrar mesas, lista as mesas, exibe seus tokens por meio de QR Codes visuais e oferece ações para imprimir, copiar link e excluir mesa.

## 12.6 `menu.client.php`

É a View pública do cardápio. Não exige login interno, mas exige token válido de mesa no parâmetro `mesa`.

A View recebe os dados de `getClientMenuData()` e apresenta:

- Nome/identificação da mesa.
- Produtos disponíveis.
- Descrição e preço.
- Imagem válida, quando disponível.
- Mensagem de erro quando o token é inválido ou o banco está indisponível.

Na versão compartilhada, a página ainda orienta o cliente a solicitar atendimento para realizar o pedido. Não há formulário de finalização de pedido implementado nesse fluxo apresentado.

---

# 13. Helpers e funções reutilizáveis

## 13.1 O que é um Helper?

Helper é um conjunto de funções auxiliares reutilizáveis. Não é uma camada mágica do PHP e não deve conter toda a lógica do negócio.

Um helper é adequado para uma operação genérica que pode ser útil em várias partes do projeto.

## 13.2 `escapeHtml()`

A função proposta para `config/helpers.php` é:

```php
function escapeHtml(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
```

Sua responsabilidade é converter caracteres especiais para que sejam exibidos como texto em contextos HTML.

Exemplo de uso:

```php
<?= escapeHtml($product['name']) ?>
```

O arquivo precisa ser incluído antes do uso:

```php
require_once __DIR__ . '/../config/helpers.php';
```

O caminho deve ser calculado em relação ao arquivo que faz o `require_once`.

## 13.3 Limites de `escapeHtml()`

`escapeHtml()` não:

- Protege uma consulta SQL.
- Valida se um ID existe.
- Verifica permissão do usuário.
- Garante que uma URL seja segura em qualquer contexto.
- Substitui validações de negócio.
- Substitui a validação de uploads.

Para consultas SQL, usar parâmetros PDO. Para permissões, validar sessão e papel no servidor. Para URLs e JavaScript, aplicar validações e escaping próprios para o contexto.

## 13.4 Outros helpers possíveis

| Nome sugerido | Uso possível |
|---|---|
| `generateSecureToken()` | Gerar tokens aleatórios |
| `isSecureTokenFormat()` | Validar formato de token |
| `formatCurrency()` | Formatar valores monetários |
| `formatDate()` | Formatar datas |
| `formatDateTime()` | Formatar data e hora |

Esses nomes são sugestões de evolução, não uma declaração de que todas essas funções já estejam implementadas.

## 13.5 Quando uma função não deve ser Helper?

Uma função que representa uma regra de negócio específica, como calcular o total de um pedido, decidir se um pagamento pode ser estornado ou finalizar uma venda, deve pertencer a uma camada de negócio ou serviço apropriado. Não deve ser colocada em um helper genérico apenas para reduzir a quantidade de arquivos.

---

# 14. Identificadores, IDs e tokens

## 14.1 ID interno

O ID é normalmente sequencial e utilizado para relacionamentos e consultas internas.

Exemplo:

| Mesa | ID |
|---|---:|
| Mesa 01 | 1 |
| Mesa 02 | 2 |
| Mesa 03 | 3 |

O ID não deve ser tratado como segredo.

## 14.2 Token público

O token é uma sequência aleatória usada para localizar um recurso por um identificador menos previsível.

No QR Code das mesas, o código apresentado utiliza:

```php
bin2hex(random_bytes(18))
```

O processo é:

1. `random_bytes(18)` gera 18 bytes aleatórios por fonte criptograficamente segura.
2. `bin2hex()` converte esses bytes para caracteres hexadecimais.
3. O resultado tem 36 caracteres.
4. O token é armazenado na coluna `qr_token` da mesa.

O formato esperado é validado por expressão regular compatível com 36 caracteres hexadecimais.

## 14.3 Token não é criptografia de um ID

O token não é o ID convertido ou criptografado. É um valor aleatório independente, armazenado no banco e associado ao registro da mesa.

A relação é:

```text
Token recebido pela URL
       │
       ▼
Consulta dining_tables.qr_token
       │
       ▼
Registro encontrado
       │
       ▼
Obtém dining_tables.id
       │
       ▼
Usa o ID nos relacionamentos internos
```

## 14.4 Token e autorização

Um token público pode funcionar como uma credencial de acesso ao recurso que representa. Por isso:

- Deve ser difícil de adivinhar.
- Deve ser armazenado e comparado corretamente.
- Deve ser revogável quando necessário.
- Não deve autorizar operações administrativas.
- A aplicação deve verificar o estado do recurso no banco.

Para tokens usados como credenciais de maior risco, deve-se avaliar armazenamento por hash, expiração, revogação, uso único e controles adicionais.

## 14.5 QR Token e CSRF Token são diferentes

| Tipo | Finalidade |
|---|---|
| QR Token | Identificar uma mesa por meio do QR Code |
| CSRF Token | Ajudar a proteger formulários contra requisições forjadas |

No fluxo de QR Code, o token CSRF fica associado à sessão administrativa e é enviado nos formulários de cadastro e exclusão. Ele não deve ser confundido com o token público da mesa.

---

# 15. Integração do QR Code com as mesas

## 15.1 Cadastro da mesa

O formulário administrativo envia `table_number` e `csrf_token` para:

```text
router.php?pagina=gerar-qrcode&action=generate-qr-code
```

O router chama `generateQrCode()`.

O controller valida método HTTP, CSRF e identificação da mesa. Depois gera o token, grava a mesa e redireciona para a tela administrativa.

## 15.2 Link associado ao QR Code

A base do link é configurada com `APP_BASE_URL`. O endereço é montado com a rota do cardápio do cliente e o token da mesa:

```text
/router.php?pagina=cardapio&mesa=TOKEN
```

O QR Code é uma representação visual desse endereço. A biblioteca `qrcodejs` desenha o QR Code no navegador.

## 15.3 Responsabilidade do JavaScript

O arquivo conhecido é:

```text
view/public/assets/js/qrcode.js
```

Ele realiza as interações de interface:

- Criar o QR Code usando a URL presente no atributo `data-qr`.
- Copiar o endereço da mesa.
- Preparar a impressão individual.
- Solicitar confirmação antes de excluir uma mesa.

O JavaScript não cria o registro no banco e não substitui o controller.

## 15.4 Acesso pelo celular

No ambiente local compartilhado, foi utilizado o endereço de rede:

```env
APP_BASE_URL=http://192.168.3.32/platypus_gestoes
```

Esse endereço é específico da rede local observada durante o desenvolvimento. Pode mudar conforme a rede, roteador ou configuração do computador.

Para o celular acessar o sistema:

1. O computador deve estar executando o Apache.
2. O celular precisa conseguir alcançar o IP ou domínio configurado.
3. O firewall deve permitir o acesso à porta HTTP/HTTPS correspondente.
4. O QR Code deve conter um endereço acessível pelo celular, nunca `localhost` do computador.

Em produção, deve-se utilizar domínio público ou endereço válido para a infraestrutura, preferencialmente com HTTPS.

---

# 16. Cardápio do cliente

## 16.1 Rota pública

A rota `cardapio` direciona para:

```text
view/public/client/menu.client.php
```

Ela é pública no sentido de não exigir login interno, mas depende de um token de mesa válido.

## 16.2 Fluxo de validação

1. A View carrega o controller do cardápio público.
2. O controller recebe `$_GET['mesa']`.
3. Confere se o token possui o formato esperado.
4. Consulta `dining_tables` pelo campo `qr_token`.
5. Confere se a mesa foi encontrada e não está `INACTIVE`.
6. Consulta produtos com `is_available = 1`.
7. Prepara as URLs de imagens válidas.
8. Devolve mesa, produtos e indicação de erro para a View.
9. A View apresenta o cardápio.

## 16.3 Identificação da mesa e pedidos

A mesa não é um usuário interno. Ela representa o contexto físico em que o pedido é realizado.

Quando o fluxo de pedidos for implementado, a relação esperada é:

```text
QR Token
   ↓
Mesa localizada em dining_tables
   ↓
ID interno da mesa
   ↓
Pedido criado em orders com table_id
   ↓
Itens registrados em order_items
```

A aplicação deve obter `table_id` a partir da mesa validada no servidor. Não deve confiar cegamente em um ID de mesa enviado pelo navegador.

## 16.4 Limitação atual

A página pública compartilhada exibe o cardápio e informa que o cliente deve solicitar atendimento à equipe para realizar o pedido. A implementação de carrinho, envio de pedido, acompanhamento individual e integração com a cozinha deve ser tratada como etapa posterior, se ainda não tiver sido concluída em outro arquivo.

---

# 17. Autenticação e autorização

## 17.1 Autenticação

A autenticação verifica se o usuário conhece suas credenciais. O controller consulta o cadastro pelo e-mail e valida a senha com as funções de hash do PHP.

A senha não deve ser armazenada em texto puro. O campo do banco é `password_hash`.

## 17.2 Sessão

Após login bem-sucedido, o sistema regenera o ID da sessão e armazena as informações necessárias, incluindo `user_id`, `user_name`, `user_email`, `user_role` e `auth`.

## 17.3 Autorização

Autorização é a verificação do que o usuário pode acessar.

O router consulta `isAuthenticated()` e `hasRole()` conforme a configuração da rota.

Autenticação e autorização são conceitos diferentes:

- Autenticação: quem está acessando?
- Autorização: essa pessoa pode executar esta ação?

## 17.4 Perfil do cliente

O cliente que utiliza o QR Code não precisa ser um registro em `users` no fluxo atual. A identificação da mesa é feita pelo token.

Se futuramente for necessário identificar pessoas individualmente, será preciso definir um mecanismo próprio de conta, sessão de cliente ou identificação de pedido. Não se deve tratar o token da mesa como identidade pessoal.

---

# 18. Uploads de imagens

## 18.1 Local de armazenamento

As imagens de produtos são armazenadas em:

```text
view/public/assets/images/products/
```

O banco guarda o nome do arquivo na coluna `products.image`, e não necessariamente o conteúdo binário da imagem.

## 18.2 Validações conhecidas

O controller de produtos realiza validações que incluem:

- Verificação do estado do upload.
- Verificação de tamanho máximo no código.
- Detecção do MIME type com `finfo`.
- Verificação do conteúdo com `getimagesize()`.
- Aceitação de JPEG, PNG e WebP.
- Nome de arquivo aleatório.
- Uso de `move_uploaded_file()`.
- Verificação do nome antes de remover arquivos.

O limite conhecido no controller é 5 MiB. A configuração local do PHP foi ajustada para `upload_max_filesize = 6M` e `post_max_size = 10M`, permitindo que o servidor aceite a requisição e o controller aplique seu próprio limite.

## 18.3 Permissões do Apache

No ambiente Void Linux observado, o worker do Apache utilizava o usuário/grupo `_apache`. A pasta de imagens precisou de grupo e permissão de escrita compatíveis com esse usuário.

A permissão deve ser concedida somente à pasta necessária, evitando liberar escrita ampla em toda a aplicação.

---

# 19. Segurança e boas práticas

## 19.1 Consultas preparadas

Usar `prepare()` e `execute()` para vincular os valores recebidos. Isso reduz o risco de injeção SQL quando os parâmetros são tratados corretamente.

## 19.2 Escaping de saída

Utilizar `escapeHtml()` ao apresentar dados não confiáveis em texto HTML.

## 19.3 Validação no servidor

Validações de HTML e JavaScript melhoram a experiência do usuário, mas não substituem a validação no PHP. Qualquer requisição pode ser enviada sem utilizar o formulário original.

## 19.4 CSRF

Operações POST que alteram estado devem possuir proteção CSRF adequada. O token deve ser imprevisível, associado à sessão e comparado de forma segura.

## 19.5 Permissões

Não confiar apenas na ausência de links na interface. A autorização precisa ocorrer no servidor, no router e/ou no controller de cada operação.

## 19.6 Uploads

Não confiar apenas na extensão informada pelo navegador. Validar MIME, conteúdo, tamanho, nome gerado e local de armazenamento.

## 19.7 Tokens públicos

O token da mesa identifica um recurso público. Não deve ser usado como autorização para excluir mesas, alterar valores, acessar dados administrativos ou executar ações financeiras.

## 19.8 Produção

Antes de disponibilizar o sistema em produção:

- Desativar exibição de erros ao usuário.
- Manter logs técnicos habilitados e protegidos.
- Utilizar HTTPS.
- Proteger `.env` e credenciais.
- Revisar permissões de arquivos e diretórios.
- Garantir índices e constraints do banco.
- Implementar backup e rotina de restauração.
- Testar permissões de cada perfil.
- Avaliar proteção contra abuso de endpoints públicos.

---

# 20. Como implementar uma nova funcionalidade

Esta seção apresenta um procedimento repetível para a equipe.

## 20.1 Etapa 1: definir a necessidade

Antes de programar, responder:

- Qual problema a funcionalidade resolve?
- Quem utilizará?
- Qual perfil terá acesso?
- Quais dados serão recebidos?
- Quais dados serão consultados ou modificados?
- Quais tabelas estão envolvidas?
- A funcionalidade precisa de uma nova rota?
- Precisa de uma operação POST?

## 20.2 Etapa 2: verificar o banco

Identificar se as tabelas e colunas necessárias já existem. Se for necessária alteração no banco, registrar a mudança no schema ou em uma migração versionada.

Não criar uma coluna ou tabela apenas para contornar uma inconsistência de código sem entender o relacionamento.

## 20.3 Etapa 3: criar ou atualizar o Controller

O controller deve validar entrada, permissões, sessão e CSRF quando aplicável. Deve executar operações de banco por meio da conexão compartilhada e retornar uma resposta consistente.

## 20.4 Etapa 4: registrar a rota

Adicionar a rota na seção `$routes` do `router.php`, definindo View, acesso e perfil.

## 20.5 Etapa 5: registrar handlers POST

Se houver cadastro, atualização ou exclusão, adicionar um handler que corresponda exatamente a `pagina`, `action` e método HTTP.

O handler deve incluir o controller, chamar a função correta e terminar o fluxo antes de carregar uma View.

## 20.6 Etapa 6: construir a View

A View apresenta os dados e os formulários. Os campos `name` enviados pelo formulário precisam corresponder aos nomes que o Controller lê em `$_POST` ou `$_FILES`.

## 20.7 Etapa 7: conectar o menu

Se a página deve aparecer na navegação, adicionar a rota em `$menusByRole`, respeitando o perfil autorizado.

## 20.8 Etapa 8: testar

Testar:

- Acesso autorizado.
- Acesso não autorizado.
- Dados válidos.
- Dados ausentes ou inválidos.
- Erros de banco.
- Requisição GET e POST conforme esperado.
- Redirecionamentos.
- Exibição de mensagens.
- Responsividade da interface.

## 20.9 Etapa 9: documentar

Atualizar este documento, o dicionário de dados e o histórico de alterações quando houver mudança relevante na arquitetura.

---

# 21. Ambiente de desenvolvimento e comandos úteis

## 21.1 Ambiente conhecido

O ambiente de desenvolvimento compartilhado utiliza:

- Void Linux.
- Apache.
- PHP 8.5 no ambiente observado durante os testes.
- MariaDB 12.3 no ambiente observado.
- Serviços gerenciados pelo runit.

As versões podem mudar e devem ser verificadas no próprio ambiente.

## 21.2 Verificar PHP

```bash
php -v
```

## 21.3 Verificar sintaxe de um arquivo PHP

```bash
php -l caminho/do/arquivo.php
```

Exemplo:

```bash
php -l router.php
```

## 21.4 Verificar Apache

```bash
sudo sv status apache
```

Reiniciar:

```bash
sudo sv restart apache
```

## 21.5 Verificar MariaDB

```bash
sudo sv status mariadb
```

## 21.6 Descobrir o IP local usado para acessar a rede

```bash
ip -4 route get 1.1.1.1
```

O endereço depois de `src` representa o IP de origem escolhido para aquela rota. Ele pode ser utilizado em `APP_BASE_URL` quando o celular estiver na mesma rede e o servidor aceitar conexões locais.

## 21.7 Conferir a configuração de upload do PHP

```bash
php --ini
php -i | grep -E 'upload_max_filesize|post_max_size|file_uploads'
```

A configuração da CLI pode diferir da configuração carregada pelo Apache. Para confirmar a configuração web, verificar o SAPI utilizado pelo Apache de forma segura e remover qualquer arquivo temporário de diagnóstico após o teste.

---

# 22. Testes e critérios de verificação

## 22.1 Router

- [ ] Rota existente carrega a View correta.
- [ ] Rota inexistente retorna 404.
- [ ] Usuário não autenticado é encaminhado ao login quando necessário.
- [ ] Usuário sem perfil autorizado recebe 403.
- [ ] Ações POST chamam a função correta.
- [ ] Links do menu correspondem às rotas registradas.

## 22.2 Usuários

- [ ] Login com credenciais válidas.
- [ ] Login com credenciais inválidas.
- [ ] Conta inativa não autentica.
- [ ] Senha armazenada como hash.
- [ ] Logout encerra a sessão.
- [ ] Perfis possuem acessos diferentes.

## 22.3 Categorias e produtos

- [ ] Cadastro de categoria.
- [ ] Consulta de categorias ativas.
- [ ] Cadastro de produto.
- [ ] Validação de preço e nome.
- [ ] Upload de imagem válida.
- [ ] Rejeição de arquivo inválido.
- [ ] Atualização de produto.
- [ ] Exclusão de produto respeitando relacionamentos.

## 22.4 QR Code e mesas

- [ ] Cadastro de mesa.
- [ ] Token gerado e persistido.
- [ ] QR Code representa uma URL acessível.
- [ ] Celular abre a aplicação pela rede configurada.
- [ ] Token inválido retorna erro apropriado.
- [ ] Mesa inativa não abre o cardápio.
- [ ] CSRF inválido impede alteração.
- [ ] Exclusão respeita vínculos com pedidos.

## 22.5 Cardápio público

- [ ] Token válido identifica a mesa correta.
- [ ] Produtos disponíveis são listados.
- [ ] Produtos indisponíveis não aparecem.
- [ ] Imagens válidas são exibidas.
- [ ] Ausência de imagem não quebra a página.
- [ ] Falha no banco apresenta mensagem adequada.
- [ ] A página funciona em celular.

---

# 23. Estado funcional e próximos passos

A tabela abaixo separa funcionalidades conhecidas de elementos que ainda precisam ser verificados ou implementados integralmente. Não deve ser usada como declaração automática de conclusão do sistema.

| Módulo | Evidência conhecida | Próxima verificação |
|---|---|---|
| Router | Rotas, permissões e handlers compartilhados | Conferir alinhamento de todas as URLs e formulários |
| Autenticação | Controller e sessão compartilhados | Testar todos os perfis e cenários de falha |
| Categorias | Cadastro e consulta compartilhados | Alinhar o campo de status do formulário ao controller |
| Produtos | CRUD e upload compartilhados | Conferir redirecionamentos após mudança para `cardapio/admin` |
| Mesas e QR Codes | Cadastro, exclusão, listagem e geração no navegador | Testar QR em celular e proteção de operações |
| Cardápio do cliente | Identificação de mesa e listagem de produtos | Implementar pedidos pelo cliente, se estiver no escopo |
| Cozinha | Rotas e tabelas relacionadas a pedidos | Verificar o fluxo operacional de ponta a ponta |
| Estoque | Tabelas de ingredientes, receitas e movimentações | Implementar e testar movimentações e baixa por receita |
| Caixa e pagamentos | Tabelas correspondentes no schema | Implementar abertura, fechamento, recebimentos e conciliação |
| Relatórios | Rota administrativa registrada | Criar relatórios a partir de dados reais consolidados |

## 23.1 Sequência recomendada de implementação

1. Concluir e testar autenticação, permissões e cadastros básicos.
2. Concluir cardápio, categorias, imagens, mesas e QR Codes.
3. Implementar o fluxo de criação e acompanhamento de pedidos.
4. Integrar o painel da cozinha e os atendentes ao fluxo de pedidos.
5. Implementar pagamentos e controle de caixa.
6. Implementar estoque e consumo de ingredientes.
7. Implementar relatórios com dados reais.
8. Executar testes integrados e preparar a apresentação do mini TCC.

Relatórios ficam entre as etapas finais porque dependem de dados confiáveis gerados pelos módulos operacionais.

---

# 24. Pontos de atenção e decisões pendentes

Os itens abaixo precisam ser revisados diretamente nos arquivos atuais antes de uma entrega final.

## 24.1 Rota pública e rota administrativa do cardápio

A convenção atual é:

- `pagina=cardapio`: cardápio público do cliente, exige token de mesa.
- `pagina=cardapio/admin`: gerenciamento administrativo, exige perfil `ADMIN`.
- `pagina=cardapio/admin/adicionar`: cadastro de produto.
- `pagina=cardapio/admin/editar`: edição de produto.

Todos os formulários, links, redirecionamentos do controller e itens de menu precisam usar essa convenção.

## 24.2 `ATTENDANT` versus `ATENDENTE`

O valor interno recomendado e utilizado na maioria dos trechos mais recentes é `ATTENDANT`. Não misturar com `ATENDENTE`, pois a comparação de strings é exata e pode negar permissões.

## 24.3 Carregamento do helper

A View pública utiliza `client.menu.controller.php`, e esse controller inclui `config/helpers.php`. Os arquivos precisam existir exatamente nos caminhos referenciados.

## 24.4 Carregamento do `.env`

A função `loadEnv()` observada exige um argumento de caminho. Não chamar `loadEnv()` sem argumento. Preferir centralizar o carregamento na configuração do banco em vez de repetir a chamada nas Views.

## 24.5 Status da categoria

O formulário de categoria compartilhado oferece `is_active`, mas o controller apresentado grava `1` fixamente. É necessário escolher se a interface permite cadastrar categorias inativas e, nesse caso, implementar a validação do valor enviado.

## 24.6 Restrição única para QR Token

A coluna `dining_tables.qr_token` deve possuir índice ou constraint `UNIQUE` para impedir duplicidade no banco. Confirmar diretamente no `schema.sql` e no banco de desenvolvimento.

## 24.7 Exclusão de mesas

Uma mesa que possui pedidos associados não deve ser apagada de maneira que comprometa o histórico. O controller já trata falha de exclusão, mas o comportamento definitivo deve ser confirmado com as foreign keys e a regra de negócio. Em muitos cenários, desativar a mesa é preferível a excluir o registro.

## 24.8 Relatórios e dados financeiros

Faturamento não é necessariamente lucro. Para calcular lucro real, o sistema precisa registrar custos de ingredientes e demais custos operacionais de forma confiável. Os campos atuais de preço de venda e pagamentos não são suficientes, por si só, para calcular lucro líquido.

## 24.9 Endereço de rede

O IP local `192.168.3.32` foi observado durante um teste. Não é um endereço permanente garantido. Atualizar `APP_BASE_URL` quando a rede ou o servidor mudar.

---

# 25. Glossário

| Termo | Significado |
|---|---|
| Apache | Servidor HTTP utilizado para entregar a aplicação |
| Backend | Parte do sistema executada no servidor |
| Frontend | Interface apresentada ao usuário |
| Controller | Componente que coordena uma funcionalidade |
| CRUD | Create, Read, Update, Delete: criar, consultar, atualizar e excluir |
| CSRF | Tipo de ataque que tenta induzir uma sessão autenticada a enviar uma requisição indesejada |
| Endpoint | Endereço que recebe uma requisição |
| Helper | Função auxiliar reutilizável |
| ID | Identificador interno de um registro |
| Model | Representação de uma entidade ou estrutura de dados |
| PDO | Interface do PHP para acesso a bancos de dados |
| QR Code | Código bidimensional que representa dados, neste projeto uma URL com token de mesa |
| Rota | Identificador de uma página ou destino no router |
| Router | Componente que interpreta rotas e direciona requisições |
| Session | Mecanismo para manter estado entre requisições de um usuário |
| SQL Injection | Ataque que tenta alterar uma consulta SQL por meio de entradas não tratadas |
| Token | Sequência de caracteres usada para identificar ou validar um recurso ou operação |
| View | Arquivo responsável pela apresentação da interface |
| XSS | Cross-Site Scripting, execução indevida de conteúdo ativo no navegador |

---

# 26. Orientação para apresentação no mini TCC

## 26.1 Problema abordado

Restaurantes precisam organizar informações de produtos, mesas, pedidos, equipe e atividades operacionais. O Platypus propõe centralizar parte dessas atividades em uma aplicação web.

## 26.2 Solução proposta

Uma aplicação PHP com banco MariaDB, organização por responsabilidades e acesso dividido por perfis. O cardápio digital utiliza QR Codes individuais para identificar mesas e direcionar o cliente à página pública correspondente.

## 26.3 Diferenciais técnicos que podem ser demonstrados

- Router central com autorização por perfil.
- Persistência relacional em MariaDB.
- Consultas parametrizadas com PDO.
- Autenticação por hash de senha.
- Proteção CSRF nas operações de mesa compartilhadas.
- Upload validado de imagens.
- Tokens aleatórios para identificação pública das mesas.
- Separação entre ID interno e token público.
- Organização das funções auxiliares.
- Interface responsiva para administração e consulta do cardápio.

## 26.4 Demonstração sugerida

1. Entrar no sistema como administrador.
2. Mostrar o gerenciamento de categorias e produtos.
3. Cadastrar uma mesa.
4. Exibir o QR Code gerado.
5. Escanear o QR Code com um celular conectado à rede acessível.
6. Mostrar a identificação correta da mesa.
7. Demonstrar a consulta de produtos disponíveis.
8. Explicar como a estrutura de banco permite associar pedidos à mesa.
9. Apresentar quais módulos já estão implementados e quais continuam como trabalho futuro.

Não apresentar uma funcionalidade como concluída se ela ainda não tiver sido testada de ponta a ponta.

## 26.5 Divisão de estudo entre integrantes

Uma divisão possível para a equipe:

| Tema | Assuntos para estudar |
|---|---|
| Arquitetura | Router, rotas, Views, Controllers e Helpers |
| Banco de dados | Tabelas, chaves, relacionamentos e consultas PDO |
| Segurança | Autenticação, autorização, sessões, CSRF, tokens e escaping |
| Interface | HTML, CSS, responsividade e JavaScript |
| Operação | Mesas, QR Codes, pedidos, cozinha, estoque, caixa e pagamentos |
| Documentação | Requisitos, diagramas, casos de uso, testes e apresentação |

A divisão é uma sugestão. A equipe deve ajustar as responsabilidades de acordo com quem implementou e conhece cada parte.

---

# 27. Histórico de revisões

| Versão | Data | Descrição |
|---|---|---|
| 1.0 | 04/11/2026 | Consolidação da arquitetura, rotas, banco, controllers, Views, helpers, tokens e integração do QR Code a partir do material compartilhado pela equipe. |

---

# Encerramento

Este documento serve como referência técnica compartilhada para estudo, manutenção e evolução do Platypus.

A documentação deve acompanhar o código. Quando uma rota, tabela, função, regra de permissão ou fluxo de negócio for alterado, a equipe deve revisar as seções correspondentes.

O objetivo não é apenas registrar onde cada arquivo está, mas permitir que qualquer integrante compreenda:

- Qual problema cada módulo resolve.
- Como os dados percorrem a aplicação.
- Onde uma nova regra deve ser implementada.
- Como reutilizar funções existentes.
- Como testar as alterações.
- Quais partes ainda precisam ser desenvolvidas.

**Documento de referência do projeto Platypus.**
