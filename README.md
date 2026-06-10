# ⏳ Cronos Kanban

O **Cronos Kanban** é um sistema moderno de gerenciamento de atividades (Kanban Board) planejado para oferecer uma experiência visual altamente premium, interações fluidas e segurança robusta. Construído como uma aplicação SPA (*Single Page Application*) através do ecossistema Laravel e Vue.js, o projeto é uma excelente demonstração de práticas modernas de arquitetura de software, tipagem estática e design centrado no usuário.

---

## 🚀 Sumário
* [Visão Geral](#-visão-geral)
* [✨ Funcionalidades em Destaque](#-funcionalidades-em-destaque)
* [💼 Para Recrutadores (Diferenciais de Negócio & UX)](#-para-recrutadores-diferenciais-de-negocio--ux)
* [💻 Para Desenvolvedores (Stack Técnica & Boas Práticas)](#-para-desenvolvedores-stack-tecnica--boas-praticas)
* [🛠 Tecnologias Utilizadas](#-tecnologias-utilizadas)
* [🔧 Instalação e Execução](#-instalação-e-execução)
* [🧪 Qualidade de Código & Testes](#-qualidade-de-código--testes)

---

## 📂 Visão Geral

O projeto foi projetado para contornar a complexidade tradicional de manter múltiplos repositórios (Frontend + Backend separados) sem abrir mão de uma experiência rica de SPA. Utilizando o **Inertia.js** como ponte, o backend em Laravel controla as rotas, regras de negócio e persistência, enquanto o frontend em Vue 3 renderiza interfaces interativas em tempo real.

O fluxo de drag-and-drop permite reordenar cartões livremente entre colunas, atualizando as posições de forma assíncrona no banco de dados de maneira transacional e protegida por usuário.

---

## ✨ Funcionalidades em Destaque

O Cronos Kanban vai além dos quadros convencionais e conta com recursos completos de produtividade:

*   **📅 Visualização em Calendário (Mensal):** Alterne instantaneamente entre a visualização clássica de Quadro (Kanban) e uma visualização em Calendário. As tarefas são organizadas dinamicamente nos dias correspondentes com base em seus prazos, respeitando todos os filtros ativos (busca por texto, prioridades, etiquetas). É possível clicar em qualquer tarefa dentro do calendário para editá-la diretamente.
*   **📊 Métricas & Produtividade:** Um painel integrado que apresenta estatísticas cruciais para o usuário, incluindo contagem de tarefas ativas, tarefas vencidas, progresso geral das checklists e gráficos visuais com a distribuição de tarefas por prioridade e a carga de trabalho atual por coluna.
*   **📋 Subtarefas com Checklist:** Crie checklists detalhadas dentro de cada tarefa. O cartão exibe o progresso textual (ex: `1/3`) e uma barra de progresso visual colorida. Também é possível marcar ou desmarcar subtarefas diretamente na visualização principal, sem abrir o modal de edição.
*   **🏷️ Etiquetas e Tags Coloridas:** Crie, edite e gerencie etiquetas personalizadas com cores distintas para categorizar seus cartões. Um sistema de filtro por cliques na barra de pesquisa permite isolar rapidamente tarefas com etiquetas específicas.
*   **📂 Arquivamento de Tarefas:** Mantenha o seu quadro principal organizado arquivando tarefas concluídas. Uma seção dedicada a itens arquivados permite visualizar o histórico ou restaurar tarefas de volta para o Kanban a qualquer momento.
*   **⏱️ Prazos Relativos Amigáveis:** Prazos de tarefas são exibidos de forma inteligente e contextual com badge de cores (Ex: "Vence hoje" em amarelo, "Atrasado há 3 dias" em vermelho, "Amanhã" ou datas amigáveis).
*   **📥 Importação e Exportação JSON:** Exporte a estrutura completa do seu quadro (etiquetas, colunas e tarefas) em um arquivo de backup em formato `.json` com um único clique. Importe arquivos de backup para recuperar ou duplicar quadros instantaneamente.

---

## 💼 Para Recrutadores (Diferenciais de Negócio & UX)

Se você está avaliando o projeto de uma perspectiva de produto, aqui estão os pontos de maior destaque:

*   **Design Premium e Limpo (Aparência Glassmorphism):** Interface desenvolvida com foco na estética moderna, utilizando bordas translúcidas sutilmente desfocadas, paleta de cores HSL cuidadosamente selecionada e suporte nativo ao modo escuro (Dark Mode) sincronizado com o sistema.
*   **Foco em Micro-interações:** Hover dinâmico em cartões que se elevam sutilmente, menus de ação contextualizados que aparecem no momento ideal e feedbacks táteis suaves na ordenação de cartões.
*   **Segurança Baseada em Tenant (Isolamento Multi-tenant):** Cada usuário possui seu próprio workspace de forma totalmente isolada. Um usuário nunca consegue visualizar, editar, mover ou excluir as colunas, tarefas ou etiquetas de outro usuário.
*   **Confirmations Personalizadas (Sem Popups Nativos):** Diálogos customizados e elegantes integrados ao design para confirmação de ações críticas (como exclusão de colunas), evitando alertas rudimentares do navegador.
*   **Notificações em Tempo Real (Toasts):** Feedback instantâneo para o usuário através da biblioteca `vue-sonner` ao reordenar, criar, editar, arquivar, importar ou remover cartões e colunas.

---

## 💻 Para Desenvolvedores (Stack Técnica & Boas Práticas)

Se você é desenvolvedor e quer analisar a qualidade da implementação técnica, o projeto adota os seguintes padrões:

*   **Tipagem Estática Estrita no PHP:** Configuração do **PHPStan (nível 7)** ativo em todas as classes, garantindo prevenção de erros de runtime. Relacionamentos do Eloquent e Traits de Factory estão totalmente documentados com tipos genéricos (`HasMany<Column, User>`, etc.).
*   **TypeScript no Frontend:** Todos os componentes do Vue utilizam Composition API com `<script setup lang="ts">`, com interfaces tipadas para `Task`, `Subtask`, `Tag`, `Column` e props de rotas geradas dinamicamente.
*   **Operações Transacionais no DB:** A reordenação de cartões (drag and drop) é processada de maneira segura em lote pelo backend dentro de uma Database Transaction (`DB::transaction`). Isso previne estados corrompidos no banco de dados caso uma das queries falhe.
*   **Tailwind CSS v4:** Utilização da versão mais recente do Tailwind, tirando proveito do compilador otimizado via Vite (`@tailwindcss/vite`) e controle de variáveis CSS no arquivo de estilos central.
*   **Testes Automatizados com Pest:** Suíte completa de testes de feature e unitários para validação de endpoints e fluxos críticos da aplicação.

---

## 🛠 Tecnologias Utilizadas

### Backend
*   **Laravel 13** (PHP 8.3+)
*   **Laravel Fortify** (Autenticação segura de rotas)
*   **Pest PHP** (Framework de testes de alta performance)
*   **Larastan/PHPStan** (Análise estática de tipagem estrita)
*   **Laravel Pint** (Formatador e alinhador de estilo de código PHP)

### Frontend
*   **Vue 3** (Composition API, TypeScript)
*   **Inertia.js** (Comunicação SPA sem necessidade de APIs REST complexas)
*   **Tailwind CSS v4** (Estilização moderna)
*   **Reka UI / Radix Vue** (Componentes de acessibilidade headless para os Modais/Diálogos)
*   **vuedraggable / Sortable.js** (Lógica de Drag & Drop livre de falhas de concorrência)
*   **Lucide Vue** (Ícones vetoriais modernos)
*   **ESLint & Prettier** (Análise e formatação do código JS/TS/Vue)

---

## 🔧 Instalação e Execução

Para rodar este projeto localmente na sua máquina, siga os passos abaixo:

### Pré-requisitos
*   **PHP >= 8.3**
*   **Composer**
*   **Node.js >= 22** e **npm**

### Passos de Configuração

1.  **Clonar o Repositório:**
    ```bash
    git clone https://github.com/MarcondesJuniorDev/cronos-kanban.git
    cd cronos-kanban
    ```

2.  **Instalar as Dependências do PHP:**
    ```bash
    composer install
    ```

3.  **Instalar as Dependências do Node.js:**
    ```bash
    npm install
    ```

4.  **Configurar as Variáveis de Ambiente:**
    Copie o arquivo `.env.example` para `.env`:
    ```bash
    cp .env.example .env
    ```
    *Nota: Por padrão, o projeto vem configurado para usar o banco de dados **SQLite**, criando um arquivo `database.sqlite` automaticamente dentro do diretório `database`.*

5.  **Gerar a Chave da Aplicação e Migrar o Banco:**
    ```bash
    php artisan key:generate
    php artisan migrate --seed
    ```

6.  **Iniciar os Servidores de Desenvolvimento:**
    Para que a aplicação funcione corretamente, você deve rodar o compilador do front-end e o servidor do back-end ao mesmo tempo:
    
    *   **Inicie o Vite (compilador assets):**
        ```bash
        npm run dev
        ```
    *   **Inicie o Laravel (servidor backend) em outro terminal:**
        ```bash
        php artisan serve
        ```
        *(Ou utilize `composer dev` caso esteja configurado no composer.json para iniciar ambos em paralelo).*

    Acesse a aplicação em seu navegador através do endereço:
    👉 **[http://localhost:8000](http://localhost:8000)**

---

## 🧪 Qualidade de Código & Testes

Para garantir a qualidade e estabilidade do código do projeto, você pode rodar os seguintes comandos de validação:

*   **Executar a Suíte de Testes (Pest):**
    ```bash
    vendor/bin/pest
    ```
*   **Análise Estática de Tipagem (PHPStan):**
    ```bash
    composer types:check
    ```
*   **Verificação de Estilo PHP (Laravel Pint):**
    ```bash
    composer lint:check
    ```
*   **Verificação de Estilo JS/Vue (ESLint):**
    ```bash
    npm run lint:check
    ```
