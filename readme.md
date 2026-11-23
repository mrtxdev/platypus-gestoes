# Platypus Gestões

Sistema web para gestão de restaurante. O projeto reúne cardápio digital acessado por QR Code, pedidos por mesa, acompanhamento da cozinha, controle de estoque, gerenciamento de usuários e relatórios operacionais.

## Sumário

- [Visão geral](#visão-geral)
- [Funcionalidades](#funcionalidades)
- [Tecnologias e requisitos](#tecnologias-e-requisitos)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Primeiro acesso](#primeiro-acesso)
- [Acesso ao sistema](#acesso-ao-sistema)
- [Como usar](#como-usar)
- [Perfis de acesso](#perfis-de-acesso)
- [Banco de dados](#banco-de-dados)
- [Fluxo de pedidos](#fluxo-de-pedidos)
- [Publicação e manutenção](#publicação-e-manutenção)
- [Manutenção e solução de problemas](#manutenção-e-solução-de-problemas)
- [Boas práticas](#boas-práticas)

## Visão geral

O Platypus Gestões é uma aplicação PHP organizada em controladores, modelos e páginas de visualização. A aplicação utiliza MySQL ou MariaDB para persistir usuários, mesas, produtos, pedidos, ingredientes, movimentações de estoque e despesas.

As páginas administrativas e operacionais compartilham um sistema visual responsivo. O cardápio público é acessado por um endereço associado ao QR Code de cada mesa.

## Funcionalidades

- **Cardápio digital:** exibe os produtos disponíveis e permite personalizar ingredientes opcionais.
- **Pedidos e carrinho:** recebe pedidos associados à mesa e permite acompanhar o resumo antes do envio.
- **Monitor da cozinha:** organiza pedidos por mesa e permite atualizar o preparo.
- **Pedidos prontos:** lista pedidos que aguardam entrega.
- **Cardápio administrativo:** gerencia produtos, imagens, categorias e receitas.
- **Mesas e QR Codes:** cadastra mesas e gera links individuais para o cardápio.
- **Ingredientes e estoque:** registra ingredientes, estoque mínimo, ajustes e movimentações.
- **Estoque rápido:** registra perdas e ajustes de ingredientes e produtos.
- **Relatórios:** consulta pedidos, vendas e despesas por período, com exportação de dados em CSV.
- **Usuários e permissões:** oferece perfis para administração, cozinha e atendimento.

## Tecnologias e requisitos

- PHP 8.0 ou superior.
- Apache com `mod_rewrite` habilitado.
- MySQL ou MariaDB compatível com o esquema SQL do projeto.
- Extensões PHP `PDO`, `pdo_mysql`, `mbstring` e `fileinfo`.
- Permissão de escrita do PHP em `view/public/assets/images/products/` para salvar imagens enviadas no cadastro de produtos.
- Navegador moderno com JavaScript habilitado para as interações do cardápio e dos QR Codes.

## Estrutura do projeto

| Caminho | Responsabilidade |
|---|---|
| `index.php` | Entrada inicial da aplicação. |
| `router.php` | Resolve páginas, autenticação, permissões e ações. |
| `config/` | Conexão com o banco e funções compartilhadas. |
| `controller/` | Regras de negócio e operações da aplicação. |
| `model/` | Representação e manipulação dos dados. |
| `view/includes/` | Cabeçalho e rodapé compartilhados. |
| `view/public/admin/` | Páginas administrativas e operacionais. |
| `view/public/client/` | Cardápio e carrinho do cliente. |
| `view/public/error/` | Páginas de erro HTTP. |
| `view/public/assets/` | CSS, JavaScript, ícones e imagens. |
| `SQL/schema.sql` | Criação do banco e das tabelas. |
| `SQL/insert.schema.sql` | Dados iniciais de demonstração. |
| `documents/` | Documentação técnica complementar. |

## Instalação

1. Coloque o projeto em uma pasta servida pelo Apache, por exemplo `htdocs/platypus_gestoes` no XAMPP ou `/var/www/html/platypus_gestoes` no Linux. As rotas e os recursos estáticos usam o caminho `/platypus_gestoes`.
2. Habilite o `mod_rewrite` e permita que o Apache leia o arquivo `.htaccess` (por exemplo, com `AllowOverride All` no VirtualHost).
3. Crie o banco e as tabelas importando `SQL/schema.sql`. O script cria o banco `restaurant_management`:

   ```bash
   mysql -u seu_usuario -p < SQL/schema.sql
   ```

4. Crie o arquivo `.env` na raiz do projeto, ao lado de `router.php`, conforme a seção [Configuração](#configuração).
5. Se desejar, importe dados de demonstração. Esse script inclui usuários com hashes ilustrativos, que não podem ser usados para entrar:

   ```bash
   mysql -u seu_usuario -p restaurant_management < SQL/insert.schema.sql
   ```

6. Siga [Primeiro acesso](#primeiro-acesso) para criar uma conta de administrador.
7. Abra `http://localhost/platypus_gestoes/` para ver a página inicial e `http://localhost/platypus_gestoes/router.php?pagina=login` para entrar no sistema.

Se o projeto estiver em uma pasta ou endereço diferente, ajuste a configuração do Apache e os caminhos `/platypus_gestoes` utilizados nas rotas da aplicação.

## Configuração

Defina as variáveis abaixo no `.env`. Use os dados do ambiente local e não publique credenciais reais.

```dotenv
DB_HOST=localhost
DB_NAME=restaurant_management
DB_USER=seu_usuario
DB_PASS=sua_senha
APP_BASE_URL=http://localhost/platypus_gestoes
```

`DB_NAME` precisa corresponder ao banco definido em `SQL/schema.sql`. A configuração de conexão usa `gestao_restaurante` como valor padrão; informe explicitamente `restaurant_management` ao usar o esquema fornecido.

O `APP_BASE_URL` é utilizado na criação dos links dos QR Codes. Para abrir o cardápio em outros dispositivos da rede, configure-o com o endereço IP ou domínio acessível pelos clientes, por exemplo `http://192.168.1.20/platypus_gestoes`. O endereço não deve terminar com uma barra.

O `.gitignore` exclui o `.env` e outros arquivos de configuração local. Cada ambiente deve manter suas próprias credenciais fora do controle de versão.

## Primeiro acesso

Gere um hash de senha localmente com o PHP (troque o texto entre aspas por uma senha forte):

```bash
php -r 'echo password_hash("troque-por-uma-senha-forte", PASSWORD_DEFAULT), PHP_EOL;'
```

Copie o hash gerado e crie a conta no banco. Substitua `HASH_GERADO` pelo valor completo:

```sql
INSERT INTO users (name, email, password_hash, role, is_active)
VALUES ('Administrador', 'admin@exemplo.com', 'HASH_GERADO', 'ADMIN', 1);
```

Depois, entre em `/platypus_gestoes/router.php?pagina=login`. Não use os hashes de exemplo de `SQL/insert.schema.sql` como credenciais: são marcadores ilustrativos, não senhas funcionais.

## Acesso ao sistema

Com o Apache configurado para servir a pasta do projeto, as páginas são acessadas pelo roteador. Substitua `localhost` pelo host configurado no seu ambiente:

| Página | Endereço |
|---|---|
| Página inicial pública | `http://localhost/platypus_gestoes/` |
| Login | `http://localhost/platypus_gestoes/router.php?pagina=login` |
| Painel | `http://localhost/platypus_gestoes/router.php?pagina=dashboard` |
| Usuários | `http://localhost/platypus_gestoes/router.php?pagina=novo-usuario` |
| Cardápio administrativo | `http://localhost/platypus_gestoes/router.php?pagina=cardapio/admin` |
| Mesas e QR Codes | `http://localhost/platypus_gestoes/router.php?pagina=mesas` |
| Monitor da cozinha | `http://localhost/platypus_gestoes/router.php?pagina=monitor-cozinha` |
| Pedidos prontos | `http://localhost/platypus_gestoes/router.php?pagina=pedidos-prontos` |
| Cardápio do cliente | Abra o QR Code da mesa ou use `http://localhost/platypus_gestoes/router.php?pagina=cardapio&mesa=TOKEN_DA_MESA` |

O roteador restringe as páginas administrativas de acordo com o perfil autenticado. Os endereços disponíveis para cada perfil também são apresentados na navegação do sistema.

## Como usar

1. Entre com uma conta de administrador.
2. Cadastre categorias, ingredientes e produtos no cardápio. Ao cadastrar um produto, informe preço, descrição, imagem opcional e as opções de estoque/receita aplicáveis.
3. Cadastre as mesas e gere seus QR Codes. Configure `APP_BASE_URL` com um endereço alcançável pelos celulares antes de imprimir os códigos.
4. Escaneie um QR Code, escolha os produtos, ajuste quantidades e ingredientes opcionais e envie o pedido. O cliente pode acompanhar os pedidos da mesa e consultar o carrinho.
5. A cozinha acompanha e atualiza os pedidos no monitor. O atendimento consulta a lista de pedidos prontos e registra a entrega.
6. Use as telas de ingredientes e estoque para registrar entradas, ajustes e perdas; consulte os relatórios para acompanhar o período.

## Perfis de acesso

| Perfil | Áreas principais |
|---|---|
| `ADMIN` | Painel, usuários, cardápio e categorias, ingredientes/estoque, mesas/QR Codes e relatórios. |
| `KITCHEN` | Monitor da cozinha e estoque rápido. |
| `ATTENDANT` | Painel, mesas/chamados e pedidos prontos. |

O roteador verifica as permissões das rotas protegidas. Use a navegação apresentada após entrar para acessar as áreas liberadas para o perfil.

## Banco de dados

O esquema mantém os dados em tabelas relacionadas. Entre as principais estão:

| Tabela | Finalidade |
|---|---|
| `users` | Contas, credenciais e perfis. |
| `dining_tables` | Mesas, tokens de QR Code e estado de ocupação. |
| `categories` e `products` | Organização e cadastro do cardápio. |
| `ingredients` e `recipes` | Ingredientes, receitas e quantidades utilizadas. |
| `orders` e `order_items` | Pedidos e itens associados. |
| `order_status_history` | Histórico de alterações de status dos pedidos. |
| `inventory_movements` e `product_stock_movements` | Histórico de movimentações de ingredientes e produtos. |
| `expenses` | Despesas registradas para os relatórios. |

Para consultar nomes de tabelas e relacionamentos atualizados, use `SQL/schema.sql` como referência.

## Fluxo de pedidos

1. A equipe cadastra mesas e gera um QR Code para cada uma.
2. O cliente abre o endereço da mesa e seleciona produtos no cardápio digital.
3. O pedido é encaminhado à cozinha, que atualiza o andamento no monitor.
4. Quando o pedido fica pronto, a equipe de atendimento consulta a página de pedidos prontos e registra a entrega.
5. A aplicação atualiza os dados operacionais usados nos relatórios.

O endpoint `view/public/client.order.status.php` é utilizado para consultar o acompanhamento dos pedidos em formato JSON; não é uma página visual.

## Publicação e manutenção

- Configure o `APP_BASE_URL` com o endereço público ou IP da rede local, incluindo `/platypus_gestoes` e sem barra no final. O host precisa ser acessível nos dispositivos que vão escanear os QR Codes; `localhost` funciona apenas no próprio computador.
- Garanta que o usuário do servidor web possa gravar em `view/public/assets/images/products/`. O sistema cria a pasta quando necessário e aceita imagens JPEG, PNG ou WebP de até 5 MB.
- Ajuste `upload_max_filesize` e `post_max_size` na configuração do PHP para permitir o limite de upload da aplicação.
- Em produção, use HTTPS, credenciais fortes e um usuário de banco com permissões somente sobre o banco da aplicação. Não publique o `.env`.
- Faça backup do banco antes de atualizar o esquema e teste a restauração do backup.

## Manutenção e solução de problemas

- **Erro de conexão com o banco:** confira `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no `.env`, além de confirmar que o servidor está ativo e que o esquema foi importado.
- **QR Code inacessível em outro aparelho:** substitua `localhost` em `APP_BASE_URL` por um IP ou domínio alcançável na rede e verifique as regras de firewall.
- **Rotas retornam erro 404:** confirme o `mod_rewrite`, a leitura do `.htaccess`, o `RewriteBase` e o caminho de instalação `/platypus_gestoes`.
- **Imagens não aparecem ou não podem ser enviadas:** verifique permissões de escrita da pasta de imagens e os limites de upload do PHP.
- **Alterações de estilo não aparecem:** atualize a página sem o cache do navegador; as páginas incluem um identificador de versão no CSS.
- **Acesso negado:** confira o perfil associado ao usuário e as permissões definidas no roteador.

## Boas práticas

- Não inclua o `.env`, senhas ou tokens em commits.
- Use senhas fortes e hashes gerados pela aplicação para as contas reais.
- Trate os dados de demonstração como conteúdo de desenvolvimento; não use os hashes ilustrativos para autenticação.
- Mantenha o PHP, o banco de dados e o servidor web atualizados.
- Faça cópias de segurança do banco antes de alterações no esquema.
- Revise as permissões e os fluxos de dados antes de disponibilizar o sistema publicamente.
