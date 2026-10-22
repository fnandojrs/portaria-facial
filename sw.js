// Service worker minimalista: nao armazena fotos, respostas de API nem dados pessoais.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
