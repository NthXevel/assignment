## 🚀 Quick Start

Follow these steps to set up the project locally:

1. **Clone the repository:**
2. **Navigate to the project folder:**
3. **Install PHP dependencies:**

    ```bash
    composer install
    ```

4. **Copy `.env` configuration:**

    ```bash
    cp .env.example .env
    ```

5. **Generate application key:**

    ```bash
    php artisan key:generate
    ```

6. **Configure the database in the `.env` file** with your local credentials.

7. **Run database migrations and seed sample data:**

    ```bash
    php artisan migrate:fresh --seed
    ```

8. **Link storage for media files:**

    ```bash
    npm install
    ```

9. **Install JavaScript and CSS dependencies:**

    ```bash
      npm run dev
    ```

10. **Start the Laravel development server:**

    ```bash
    php artisan serve
    ```
