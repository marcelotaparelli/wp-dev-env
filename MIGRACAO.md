# Migração: do ambiente Docker para o servidor final

Este documento descreve como levar o site desenvolvido neste ambiente
Docker para o servidor de produção — com ou sem WordPress já
instalado lá.

## O que você está migrando

Com este `docker-compose.yml`, só existem duas coisas que carregam
informação real do site:

- **`wp-content/themes/boilerplate`** — todo o código do tema custom
  (renomeie essa pasta para o nome do seu tema ao iniciar um projeto
  novo; não há plugins no projeto).
- **O banco de dados** do container `db` — posts, páginas, usuários,
  menus, configurações do tema, opções salvas no Site Editor.

O pacote de migração é sempre esses dois itens juntos.

## Passo 1 — Exportar o banco de dados

Com os containers rodando (`docker compose up -d`):

```bash
docker compose run --rm wpcli db export backup.sql
```

O arquivo `backup.sql` aparece na raiz do projeto (a pasta é montada
no container).

## Passo 2 — Levar os arquivos pro servidor

Envie por FTP/SFTP ou `scp`:

- A pasta `wp-content/themes/boilerplate` inteira.
- O arquivo `backup.sql`.

## Passo 3A — Se o servidor já tem WordPress instalado

1. Coloque a pasta do tema em `wp-content/themes/` do servidor.
2. Importe o `backup.sql` no banco de dados do servidor (via
   phpMyAdmin da hospedagem, Adminer, ou linha de comando, dependendo
   do que a hospedagem oferece).
3. Se o servidor tiver WP-CLI disponível via SSH, rode a partir da
   raiz do WordPress lá:
   ```bash
   wp search-replace 'http://localhost:8080' 'https://seudominio.com' --all-tables
   ```
   Isso corrige as URLs gravadas no banco sem corromper dados
   serializados (um find-replace comum de editor de texto ou SQL
   pode quebrar essas strings — ver observação no final).
4. No `/wp-admin`, ative o tema em **Aparência > Temas**.
5. Vá em **Configurações > Links Permanentes** e clique em "Salvar"
   (sem mudar nada) para regravar as regras de rewrite do Apache/Nginx
   do servidor.

## Passo 3B — Se o servidor ainda não tem WordPress

1. Instale o WordPress no servidor primeiro (instalador da
   hospedagem, Softaculous, ou manualmente).
2. Depois de instalado e com um banco novo criado, repita os passos
   do **3A** a partir do passo 1 — mas antes de importar o
   `backup.sql`, garanta que a tabela de opções do banco novo esteja
   vazia/limpa (evita conflito com as configurações do instalador
   padrão).

## Se a hospedagem não tiver WP-CLI via SSH

Nem toda hospedagem oferece acesso SSH com WP-CLI. Duas alternativas:

- **Plugin All-in-One WP Migration**: instala no destino, importa um
  pacote exportado e resolve o search-replace de URL sozinho, pela
  interface do admin — sem precisar de terminal.
- **Editar manualmente as opções `siteurl` e `home`** na tabela
  `wp_options` via phpMyAdmin da hospedagem, e revisar campos que
  guardam URL em `wp_posts` (imagens/links inseridos no conteúdo) —
  mais trabalhoso e sujeito a erro, use só se as outras opções não
  estiverem disponíveis.

## Por que não usar find-replace comum na URL

O WordPress grava várias configurações como **dados serializados
PHP**, onde o tamanho da string fica gravado no meio do dado:

```
a:1:{s:9:"site_url";s:21:"http://localhost:8080";}
```

Um find-replace de texto simples troca a URL mas não recalcula esse
tamanho, corrompendo o dado — o WordPress não consegue mais ler
aquela opção (sintomas: editor quebrando, widgets sumindo,
configurações resetando). O `wp search-replace` do WP-CLI existe
justamente para fazer essa troca de forma segura.

## Checklist rápido antes de considerar a migração concluída

- [ ] Tema ativo aparece corretamente em **Aparência > Temas**
- [ ] Links permanentes salvos novamente (Passo 3A.5)
- [ ] Nenhuma URL `localhost:8080` sobrando (procurar no banco ou no
      HTML renderizado)
- [ ] Certificado HTTPS ativo no domínio final (troque `http://` por
      `https://` no search-replace, se aplicável)
- [ ] `WORDPRESS_DEBUG` desligado — isso é config só do ambiente Docker
      de dev; confirme que o servidor de produção não está com
      exibição de erros PHP ligada