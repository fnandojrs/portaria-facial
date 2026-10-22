# Portaria Facial — versão organizada

Arquivos gerados a partir do index.php e config.php enviados.

## Instalação

1. Faça um backup da pasta `/var/www/html/portaria` antes de substituir arquivos.
2. Extraia as pastas `api/`, `paginas/` e `assets/` e os arquivos `index.php` e `config.php` na raiz `/var/www/html/portaria`. Não apague `manifest.webmanifest`, `sw.js`, ícones ou pasta `python/` existentes.
3. Mantenha banco e imagens de referência em `/var/lib/portaria` e o reconhecedor em `/opt/portaria` (nada é migrado ou excluído).
4. Verifique: `php -l /var/www/html/portaria/index.php` e teste login, cadastro, edição, terminal e movimentações.
5. Se a PWA ainda mostrar a tela antiga, force recarregamento e confira o cache do Service Worker.

A estrutura mantém os endereços `?pagina=terminal`, `?pagina=cadastro`, `?pagina=editar`, `?pagina=movimentacoes`. As requisições POST continuam indo para `index.php`.

A página `comandos_ocultos.php` foi incluída sem link no menu e exige sessão administrativa.

**Aviso:** não execute em ambiente de produção sem testes funcionais. Não publique banco, fotos reais ou senhas no GitHub.
