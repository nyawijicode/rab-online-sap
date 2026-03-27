<div x-data="{
    scanner: null,
    isScanning: false,
    hasPermission: false,
    permissionDenied: false,
    cameras: [],
    currentCameraIndex: 0,
    scannedResult: '',
    
    async checkPermission() {
        try {
            const result = await navigator.permissions.query({ name: 'camera' });
            if (result.state === 'granted') {
                this.hasPermission = true;
                this.permissionDenied = false;
            } else if (result.state === 'denied') {
                this.permissionDenied = true;
                this.hasPermission = false;
            }
        } catch (e) {
            console.log('Permission API not supported');
        }
    },
    
    async requestPermission() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(track => track.stop());
            this.hasPermission = true;
            this.permissionDenied = false;
            await this.getCameras();
        } catch (error) {
            console.error('Permission denied:', error);
            this.permissionDenied = true;
            this.hasPermission = false;
        }
    },
    
    async getCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.cameras = devices.filter(device => device.kind === 'videoinput');
        } catch (error) {
            console.error('Error getting cameras:', error);
        }
    },
    
    async startScanning() {
        if (!this.hasPermission) {
            await this.requestPermission();
            if (!this.hasPermission) return;
        }
        
        this.isScanning = true;
        this.scannedResult = '';
        
        if (typeof Html5Qrcode === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode';
            script.onload = () => this.initScanner();
            document.head.appendChild(script);
        } else {
            this.initScanner();
        }
    },
    
    async initScanner() {
        if (this.scanner) {
            try {
                await this.scanner.stop();
                await this.scanner.clear();
            } catch (e) {
                console.log('Stop error:', e);
            }
            this.scanner = null;
        }
        
        const cameraId = this.cameras[this.currentCameraIndex]?.deviceId;
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        this.scanner = new Html5Qrcode('reader');
        
        try {
            await this.scanner.start(
                cameraId || { facingMode: 'environment' },
                config,
                (decodedText) => {
                    // Simpan hasil scan
                    this.scannedResult = decodedText;
                    
                    // Set nilai ke wire
                    $wire.set(window.scannerTarget || 'data.global_scan', decodedText);
                    
                    // Stop scanner
                    if (this.scanner) {
                        this.scanner.stop().catch(e => console.log(e));
                        this.scanner = null;
                    }
                    this.isScanning = false;
                },
                (error) => {
                    // Ignore scan errors
                }
            );
        } catch (err) {
            console.error('Failed to start scanner:', err);
            alert('Gagal memulai kamera. Pastikan izin kamera sudah diberikan.');
            this.stopScanning();
        }
    },
    
    async stopScanning() {
        if (this.scanner) {
            try {
                await this.scanner.stop();
                await this.scanner.clear();
            } catch (e) {
                console.log('Scanner stop error:', e);
            }
            this.scanner = null;
        }
        this.isScanning = false;
    },
    
    async switchCamera() {
        if (this.cameras.length <= 1) return;
        
        this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
        
        if (this.isScanning && this.scanner) {
            await this.initScanner();
        }
    },
    
    resetScan() {
        this.scannedResult = '';
        this.startScanning();
    },
    
    closeModal() {
        this.stopScanning();
        this.$dispatch('close-modal');
    },
    
    init() {
        this.checkPermission();
    }
}" x-init="init()" x-on:close-modal.window="stopScanning()" class="p-4 bg-white dark:bg-gray-900">

    <!-- Security Warning -->
    <div x-show="!window.isSecureContext && window.location.hostname !== 'localhost'"
        class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-lg text-sm border border-red-200 dark:border-red-800">
        <strong>⚠️ Peringatan Keamanan:</strong> Fitur ini memerlukan koneksi <strong>HTTPS</strong> untuk mengakses
        kamera.
    </div>

    <!-- Permission Denied Warning -->
    <div x-show="permissionDenied && !isScanning && !scannedResult"
        class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-lg text-sm border border-yellow-200 dark:border-yellow-800">
        <strong>⚠️ Akses Kamera Ditolak</strong><br>
        Silakan izinkan akses kamera di pengaturan browser Anda dan coba lagi.
    </div>

    <!-- Hasil Scan Berhasil -->
    <div x-show="!isScanning && scannedResult" class="text-center py-6">
        <!-- Icon Success -->
        <div class="mb-4">
            <div
                class="w-20 h-20 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">
            Scan Berhasil!
        </h3>

        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Barcode/QR Code telah berhasil dipindai
        </p>

        <!-- Hasil Scan - FIXED: Responsive untuk text panjang -->
        <div
            class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-green-500 dark:border-green-600 max-w-full">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Hasil Scan:</p>
            <p class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100 break-all overflow-wrap-anywhere whitespace-pre-wrap max-h-32 overflow-y-auto"
                x-text="scannedResult"></p>
        </div>
        <br>
        <!-- Tombol Actions -->
        <div class="space-y-3">
            <button @click="resetScan()" type="button"
                style="background-color: #16a34a !important; color: white !important;"
                class="w-full inline-flex items-center justify-center px-6 py-3 font-bold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 hover:opacity-90">
                <svg class="w-5 h-5 mr-2" style="color: white !important;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Scan Ulang
            </button>

            <!-- FIXED: Tombol Selesai dengan method closeModal() -->
            {{-- <button @click="closeModal()" type="button"
                class="w-full px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-all duration-200 border border-gray-300 dark:border-gray-600">
                Selesai
            </button> --}}
        </div>
    </div>

    <!-- Button untuk request permission atau mulai scan -->
    <div x-show="!isScanning && !scannedResult" class="text-center py-8">
        <div class="mb-6">
            <svg class="w-24 h-24 mx-auto text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                </path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>

        <!-- Button Request Permission -->
        <div x-show="!hasPermission" class="mb-4">
            <button @click="requestPermission()" type="button"
                style="background-color: #2563eb !important; color: white !important;"
                class="w-full inline-flex items-center justify-center px-6 py-3 font-bold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 hover:opacity-90">
                <svg class="w-5 h-5 mr-2" style="color: white !important;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                    </path>
                </svg>
                Izinkan Akses Kamera
            </button>
        </div>

        <!-- Button Start Scanning -->
        <div x-show="hasPermission" class="mb-4">
            <button @click="startScanning()" type="button"
                style="background-color: #16a34a !important; color: white !important;"
                class="w-full inline-flex items-center justify-center px-6 py-3 font-bold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 hover:opacity-90">
                <svg class="w-5 h-5 mr-2" style="color: white !important;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                    </path>
                </svg>
                Scan Barcode/QR Code
            </button>
        </div>

        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 font-medium">
            Klik tombol untuk memulai scanning
        </p>

        <!-- Tombol Batal -->
        <div class="mt-6">
            <button @click="closeModal()" type="button"
                class="w-full px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-all duration-200 border border-gray-300 dark:border-gray-600">
                Batal
            </button>
        </div>
    </div>

    <!-- Scanner Area -->
    <div x-show="isScanning" x-cloak>
        <div id="reader" wire:ignore
            class="rounded-lg overflow-hidden border-2 border-gray-300 dark:border-gray-600 mb-4 bg-black"></div>

        <!-- Controls -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
            <!-- Switch Camera Button -->
            <button x-show="cameras.length > 1" @click="switchCamera()" type="button"
                style="background-color: #374151 !important; color: white !important;"
                class="inline-flex items-center justify-center px-4 py-2.5 font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg hover:opacity-90">
                <svg class="w-5 h-5 mr-2" style="color: white !important;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Ganti Kamera
            </button>

            <!-- Cancel Button -->
            <button @click="closeModal()" type="button"
                style="background-color: #dc2626 !important; color: white !important;"
                class="inline-flex items-center justify-center px-4 py-2.5 font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg hover:opacity-90">
                <svg class="w-5 h-5 mr-2" style="color: white !important;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Tutup
            </button>
        </div>
    </div>
</div>

<style>
    /* Styling untuk library scanner - Support Dark Mode */
    #reader {
        border: none !important;
        min-height: 400px !important;
        background-color: #000 !important;
    }

    #reader video {
        border-radius: 0.5rem !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    #reader__scan_region {
        border-radius: 0.5rem !important;
    }

    #reader canvas {
        border-radius: 0.5rem !important;
    }

    /* Sembunyikan semua tombol dan UI default dari library */
    #reader__dashboard,
    #reader__dashboard_section,
    #reader__dashboard_section_csr,
    #reader__dashboard_section_swaplink,
    #reader__header_message,
    #reader button,
    #reader__camera_selection,
    #reader__camera_permission_button,
    #reader__filescan_input,
    #reader__dashboard_section_fsr {
        display: none !important;
    }

    /* Dark mode adjustments untuk Filament */
    .dark #reader {
        background-color: #000 !important;
    }

    /* Animasi untuk transisi smooth */
    [x-cloak] {
        display: none !important;
    }

    /* Force button visibility */
    button {
        position: relative !important;
        z-index: 10 !important;
    }

    /* FIXED: Responsif untuk text panjang */
    .overflow-wrap-anywhere {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    /* Custom scrollbar untuk hasil scan */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 3px;
    }

    .dark .overflow-y-auto::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
    }
</style>