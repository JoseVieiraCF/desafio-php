# 💰 Laravel Wallet App

Uma aplicação desenvolvida em **Laravel** que simula uma **carteira financeira digital**, permitindo que usuários **cadastrados depositem, transfiram e revertam transações** de forma segura e idempotente.

---

## 🚀 Funcionalidades

- 🧾 Cadastro e autenticação de usuários  
- 💸 Depósitos e transferências entre carteiras  
- 🔄 Reversão de transações (depósitos e transferências)  
- 🧮 Validação de saldo antes das operações  
- 🔑 Controle de idempotência para evitar duplicações  
- 🔒 Controle transacional com `lockForUpdate()`  
- 🧠 Arquitetura limpa e baseada em **Services + Models + Controllers**
- 🧰 Testes de feature

---

## 🧱 Requisitos

- Docker e Docker Compose instalados  

---

## 🐳 Executando com Docker

- docker compose up -d

---

## Copie o arquivo .env.example ou .env.testing

 - cp .env.example .env

---

## Instale dependências:

- docker compose exec application composer install
- docker compose exec application npm install
- docker compose exec application npm run build

---

## Gere a chave da aplicação

- docker compose exec application php artisan key:generate

---

## Execute as migrations

- docker compose exec application php artisan migrate

---

## Caso tenha erros com permissões da pasta storage rode o comando abaixo (importante ⚠️)

- docker compose exec application chown -R www-data:www-data /var/www/storage

---

## Testes

- docker compose exec application php artisan test


