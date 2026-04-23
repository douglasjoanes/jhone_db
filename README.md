# Jhone DB

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892bf.svg)](https://php.net)

O **Jhone Db** é um componente de abstração de banco de dados (ORM) minimalista, extraído do ecossistema **Jhonestack Core**. Ele foi desenvolvido para ser leve, independente e seguro, permitindo que você utilize os padrões *Active Record* e *Query Builder* em qualquer projeto PHP.

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
````

## ⚙️Configuração Inicial
Para começar, inicialize a conexão e injete-a no Model base da sua aplicação:

````bash
use Jhonestack\Database\DatabaseFactory;
use Jhonestack\Database\Model;

$config = [
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'port'     => '3306',
    'database' => 'nome_do_banco',
    'username' => 'usuario',
    'password' => 'senha',
    'charset'  => 'utf8mb4'
];

$pdo = DatabaseFactory::create($config);
Model::setConnection($pdo);
````

## 📖 Guia de Uso
## Definindo um Model

```bash
namespace App\Models;

use Jhonestack\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['nome', 'email', 'senha'];
    protected bool $usesSoftDeletes = false;
}
````

## Consultas com Query Builder

```bash
// Buscar todos os usuários ativos
$users = User::query()
    ->where('status', 'ativo')
    ->orderBy('nome', 'ASC')
    ->get();

// Buscar um registro específico
$user = User::find(1);
````

## Inserção e Atualização (Active Record)

```bash
// Buscar todos os usuários ativos
$users = User::query()
    ->where('status', 'ativo')
    ->orderBy('nome', 'ASC')
    ->get();

// Buscar um registro específico
$user = User::find(1);
````

## Exclusão

```bash
$user = User::find(1);
$user->delete();
````

## 🛠️ Estrutura do Projeto

```bash
src/
├── DatabaseFactory.php  # Gerenciador de conexão
├── Model.php            # Classe base para entidades
└── QueryBuilder.php     # Motor de construção de queries
````
