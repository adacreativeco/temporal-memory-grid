    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-xs text-gray-500">
                <div>
                    © <?php echo date('Y'); ?> <strong>Temporal Memory Grid</strong> — <a href="https://adacreative.co" target="_blank" class="font-semibold text-gray-700 hover:text-blue-600">ADA Creative Co.</a> (<a href="mailto:git@adacreative.co" class="text-blue-600 hover:underline">git@adacreative.co</a>)
                </div>
                <div>
                    Apache License 2.0 • All Rights Reserved
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
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
        
        // Format date for display according to active locale
        function formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleString(window.TMG_LOCALE || 'en-US');
        }
        
        // Format number with thousands separator according to active locale
        function formatNumber(num) {
            if (num === null || num === undefined || isNaN(num)) return '-';
            return new Intl.NumberFormat(window.TMG_LOCALE || 'en-US').format(num);
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
