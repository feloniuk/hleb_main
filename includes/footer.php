</div><!-- /container -->

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> ТОВ "Одеський Коровай" - Система управління</p>
                </div>
                <div class="col-md-6 text-end" id="clock">
                    <!-- Годинник буде додано через JavaScript -->
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        // Функція відображення годинника
        function displayTime() {
            const now = new Date();
            const day = now.getDate();
            const month = now.getMonth() + 1;
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            document.getElementById("clock").textContent = 
                day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ':' + seconds;
        }

        // Оновлення часу кожну секунду
        displayTime();
        setInterval(displayTime, 1000);
    </script>
</body>
</html>