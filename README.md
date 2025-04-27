# EAIMidTerm_Group5

----------------------------------------------------------------------------------------------------------------------------------------------------
Notes Before Running :
* The Program was originally made while connecting to an onine mysql database.
* The Program also needs to have an OPENAI_API_KEY variable in the reviewService .env for the needs of sentiment analysis
* Make sure to do composer update in every project first

To run the program first :
----------------------------------------------------------------------------------------------------------------------------------------------------
1. Run the userService API :
```bash
cd userService
php -S localhost:8001 -t public
```
2. Run the appointmentService API :
```bash
cd appointmentService
php -S localhost:8002 -t public
```
3. Run the reviewService API :
```bash
cd reviewrService
php -S localhost:8003 -t public
```
4. From the root folder run the index.php :
```bash
php -S localhost:8080
```
----------------------------------------------------------------------------------------------------------------------------------------------------
