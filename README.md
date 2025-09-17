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

6. **Configure the database in the `.env` file** with your local credentials. (GO in the file and change the sqllite to mysql and put what name you want for database and create it in your own computer's database) 

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

11. **Start the Laravel development servers on three different ports:**

    Since this project requires three separate services running on different ports, follow these steps:

    **Manually Run Servers in Separate Terminals**

    1. **Open the first terminal window** and run the following command (this will run the main Laravel server on the default port, usually `8000`):

       ```bash
       php artisan serve
       ```

    2. **Open the second terminal window** and run the following command to start the server on port `8001`:

       ```bash
       php artisan serve --port=8001
       ```

    3. **Open the third terminal window** and run the following command to start the server on port `8002`:

       ```bash
       php artisan serve --port=8002
       ```

    Now, your application will be running on three different ports:
    - http://localhost:8000
    - http://localhost:8001
    - http://localhost:8002

12. **Configure Service URLs in `.env` file**:

   In your `.env` file, ensure that the following **service URLs** are correctly set with the appropriate ports:

   ```env
   USERS_SERVICE_BASE_URL=http://127.0.0.1:8001
   STOCK_SERVICE_BASE_URL=http://127.0.0.1:8001
   ORDERS_SERVICE_BASE_URL=http://127.0.0.1:8002
   PRODUCTS_SERVICE_BASE_URL=http://127.0.0.1:8001
   BRANCHES_SERVICE_BASE_URL=http://127.0.0.1:8001

13. **Running the Laravel Scheduler**

To ensure the scheduled tasks (`orders:auto-approve-urgent`) run as expected, use the following command:

```bash
php artisan schedule:work