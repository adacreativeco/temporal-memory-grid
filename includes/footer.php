    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-gray-500 text-sm">
                    © 2024 Temporal Memory Grid - Zaman Akışı Analiz Motoru
                </div>
                <div class="text-gray-500 text-sm">
                    Son Güncelleme: <span id="last-update">-</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Update last update time
        document.getElementById('last-update').textContent = new Date().toLocaleString('tr-TR');
        
        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Loading spinner
        function showLoading() {
            return '<div class="flex justify-center items-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div></div>';
        }
        
        // Error message
        function showError(message) {
            return '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">' + message + '</div>';
        }
        
        // Success message
        function showSuccess(message) {
            return '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">' + message + '</div>';
        }
        
        // Format date for display
        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('tr-TR');
        }
        
        // Format number with thousands separator
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // API helper function
        async function apiCall(endpoint, params = {}) {
            function resolveEndpoint(ep) {
                if (ep === 'timeseries/aggregate') return 'timeseries.php';
                if (ep === 'timeseries/trend') return 'trend.php';
                if (ep === 'timeseries/anomalies') return 'anomalies.php';
                return ep;
            }
            const url = new URL('/api/v1/' + resolveEndpoint(endpoint), window.location.origin);
            url.searchParams.append('api_key', 'temporal_grid_api_key_2024');
            
            Object.keys(params).forEach(key => {
                if (params[key] !== null && params[key] !== undefined) {
                    url.searchParams.append(key, params[key]);
                }
            });
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'API request failed');
                }
                
                return (data && data.success && data.data) ? data.data : data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }
    </script>
</body>
</html>
