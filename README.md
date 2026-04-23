# Jhone DB

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892bf.svg)](https://php.net)

O **Jhonestack Database** é um componente de abstração de banco de dados (ORM) minimalista, extraído do ecossistema **Jhonestack Core**. Ele foi desenvolvido para ser leve, independente e seguro, permitindo que você utilize os padrões *Active Record* e *Query Builder* em qualquer projeto PHP.

## 🚀 Recursos Principais

- **DatabaseFactory**: Gerenciamento de conexão Singleton (MySQL e PostgreSQL).
- **Query Builder Fluente**: Construção de queries SQL de forma legível e encadeada.
- **Model Abstrato**: Implementação de classes de modelo com suporte a `fillable` e `save()`.
- **Segurança**: Uso rigoroso de *Prepared Statements* para evitar SQL Injection.
- **Relacionamentos**: Suporte nativo para `hasMany`, `hasOne` e `belongsTo`.
- **Soft Deletes**: Gestão de exclusão lógica de registros.

## 📦 Instalação

Adicione o repositório ao seu arquivo `composer.json` ou instale via terminal (ajuste o nome do pacote conforme sua configuração no Packagist):

```bash
composer require douglas-joanes/jhonestack-db
