# Ambiente de desenvolvimento WordPress (Docker)

Este repositório é um **boilerplate neutro** para criar novos sites
WordPress com um ambiente de desenvolvimento Docker. Para começar um
projeto novo, duplique este repositório e renomeie a pasta do tema
(`wp-content/themes/boilerplate` → `wp-content/themes/meu-novo-site`)
substituindo o slug `boilerplate` em todo o código do tema.
O `.gitignore` já acompanha temas próprios com qualquer nome e ignora os
exemplos padrão do WordPress.

## 1. Como usar

```bash
cp .env.example .env
docker compose up -d
```

- Site: http://localhost:8080
- WP-CLI (exemplo): `docker compose run --rm wpcli plugin list`

Ao iniciar, o container garante que o Apache (`www-data`) possa ler os temas
montados. Isso evita que um tema copiado com arquivos `0600` desapareça de
**Aparência > Temas**. O ajuste acrescenta leitura e acesso a diretórios;
não altera a permissão de escrita.

Se precisar inspecionar o banco diretamente em algum momento (debug de
`theme.json`/Site Editor, checar `wp_options`, etc.), dá pra acessar
via linha de comando sem precisar de um serviço extra:

```bash
docker compose exec db mysql -u wp_user -p wordpress
```

Ou adicionar um serviço de phpMyAdmin pontualmente, se preferir
interface gráfica nesse momento.

Na primeira vez, acesse http://localhost:8080 e finalize a instalação
(idioma, usuário admin, etc.) pelo assistente do WordPress.

Para parar: `docker compose down` (os dados do banco ficam salvos no
volume `db_data`; para apagar tudo: `docker compose down -v`).

## 2. Por que só o `wp-content` está no volume

Só a pasta `wp-content` (temas, plugins, uploads) é montada do seu
computador. O "core" do WordPress fica dentro do container, baixado
pela imagem oficial. Isso é proposital: **é exatamente essa pasta
`wp-content` que você vai levar para o servidor final**, então já
trabalhamos versionando e organizando só o que realmente importa.

## 3. Plano de migração (com ou sem WordPress no destino)

A estratégia cobre os dois cenários com o mesmo processo:

**Cenário A — servidor já tem WordPress instalado**
1. Exportar o banco: `docker compose run --rm wpcli db export backup.sql`
2. Copiar `wp-content/themes/seu-tema` e `wp-content/plugins/*` (os
   que você usa) para o servidor.
3. Importar o `backup.sql` no banco do servidor.
4. Rodar o search-replace de URL (essencial, o WP grava URLs absolutas
   no banco):
   ```bash
   wp search-replace 'http://localhost:8080' 'https://seudominio.com' --all-tables
   ```
5. Ativar o tema e conferir plugins.

Em uma hospedagem fora deste Compose, confira as permissões ao copiar o tema:
diretórios precisam permitir acesso ao usuário do servidor web e arquivos como
`style.css` precisam permitir leitura.

**Cenário B — servidor não tem WordPress ainda**
1. Suba o WordPress lá (via instalador da hospedagem, Softaculous, ou
   o próprio `docker-compose.yml` deste projeto adaptado com domínio
   real).
2. Repita os mesmos passos do Cenário A a partir do passo 1.

Ou seja: **o pacote que você entrega é sempre o mesmo** — dump do
banco + pasta `wp-content` — o que muda é só se você precisa instalar
o WordPress "vazio" antes ou não. Por isso vale já deixar pronto:
- Um `wp-content` limpo, só com o que é seu (tema/plugins custom).
- Um dump de banco atualizado.
- Um `.htaccess`/config de permalinks documentado, se você customizar.

Ferramentas que ajudam bastante nesse processo: **WP-CLI**
(`search-replace`, `db export/import`) e, se preferir algo com
interface, o plugin **All-in-One WP Migration** (empacota tudo em um
arquivo só e resolve URLs automaticamente).

## 4. Forma profissional de implementar um design pronto no WordPress

Colar CSS solto em cima dos blocos padrão funciona, mas gera dívida
técnica rápido (regras difíceis de rastrear, especificidade
bagunçada, nada reaproveitável). Para implementar um design já pronto,
o caminho mais limpo hoje é:

### a) Tema próprio, não filho de tema genérico
Crie um tema customizado (pasta em `wp-content/themes/seu-tema`) em
vez de sobrepor CSS em um tema de terceiros. Isso te dá controle total
sobre marcação e estilos, sem herdar regras que você depois precisa
anular. Este boilerplate já nasce com um tema base pronto pra
renomear e customizar.

### b) `theme.json` como fonte da verdade do design system
Em vez de estilizar blocos um a um, defina cores, tipografia,
espaçamentos e tamanhos permitidos no `theme.json`. O editor (Gutenberg)
passa a oferecer só as opções do seu design, e os blocos já nascem
estilizados de acordo — sem CSS avulso por cima. É basicamente o seu
design tokenizado.

### c) Blocos customizados para peças que os blocos nativos não cobrem
Para seções específicas do layout (um hero, um carrossel, um grid de
cases), duas opções profissionais:
- **Blocos nativos com `@wordpress/create-block`** (JS/React) — mais
  flexível e é o padrão "moderno" do WordPress.
- **ACF Blocks** (Advanced Custom Fields) — mais rápido de fazer com
  PHP + Twig/Handlebars, bom se o time é mais PHP do que JS.

### d) CSS organizado e com build
- Um `style.css`/SCSS por componente/bloco, não um arquivo gigante
  global.
- Use `@wordpress/scripts` (ou Vite) para compilar SCSS/JS, com
  enqueue condicional (só carrega o CSS/JS do bloco que está na
  página).
- Evite `!important` e overrides genéricos em `.wp-block-*`; prefira
  classes próprias aplicadas via variações de bloco.

### Resumo prático
Dado que você já tem o design pronto: crie um tema custom, defina o
design system no `theme.json`, use blocos nativos para conteúdo comum
(texto, imagem, colunas) e construa blocos customizados (create-block
ou ACF Blocks) só para as peças de layout que fogem do padrão. Isso
te dá um site rápido, fácil de dar manutenção, e sem CSS solto por
cima dos blocos.