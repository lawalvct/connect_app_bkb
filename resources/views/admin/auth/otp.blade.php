<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OTP Verification - Admin Portal</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#A20030',
                        'primary-light': '#A200302B',
                        'background': '#FAFAFA'
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background min-h-screen">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">

            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 flex items-center justify-center bg-primary rounded-full">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Security Verification</h2>
                <p class="mt-2 text-sm text-gray-600">Enter the verification code sent to your email</p>
                <p class="text-sm text-primary font-medium">{{ session('admin_email') }}</p>
            </div>

            <!-- OTP Form -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('admin.auth.verify-otp.post') }}"
                  x-data="otpForm()"
                  x-init="init()"
                  x-ref="form">
                @csrf

                <div class="bg-white rounded-lg shadow-md p-8">
                    <div class="space-y-6">

                        <!-- OTP Input -->
                        <div>
                            <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                            <div class="flex space-x-2 justify-center">
                                <!-- OTP Input Fields -->
                                <div class="flex space-x-2">
                                    <template x-for="(digit, index) in digits" :key="index">
                                        <input
                                            type="text"
                                            maxlength="1"
                                            :name="'otp_' + index"
                                            x-model="digit.value"
                                            @input="handleInput(index, $event)"
                                            @keydown="handleKeydown(index, $event)"
                                            @paste="handlePaste($event)"
                                            class="w-12 h-12 text-center text-lg font-semibold border {{ $errors->has('otp') ? 'border-red-500' : 'border-gray-300' }} rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                            autocomplete="off"
                                            inputmode="numeric"
                                        >
                                    </template>
                                </div>
                            </div>

                            <!-- Hidden input for the full OTP -->
                            <input type="hidden" name="otp" x-ref="hiddenOtp" :value="getFullOtp()">

                            @error('otp')
                                <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="button"
                                    :disabled="loading || !isOtpComplete"
                                    @click="loading = true; $refs.hiddenOtp.value = getFullOtp(); console.log('Form submitting with OTP:', getFullOtp()); $refs.form.submit();"
                                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!loading" class="flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    Verify Code
                                </span>
                                <span x-show="loading" class="flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Verifying...
                                </span>
                            </button>
                        </div>

                        <!-- Resend OTP -->
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Didn't receive the code?</p>
                            <button type="button"
                                    @click="resendOtp()"
                                    :disabled="resending || countdown > 0"
                                    class="mt-2 text-sm text-primary hover:text-primary-dark font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!resending && countdown <= 0">Resend Code</span>
                                <span x-show="resending">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>
                                    Sending...
                                </span>
                                <span x-show="countdown > 0" x-text="'Resend in ' + countdown + 's'"></span>
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Back to Login -->
                <div class="text-center">
                    <a href="{{ route('admin.auth.login') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Back to Login
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
        function otpForm() {
            return {
                loading: false,
                resending: false,
                countdown: 0,
                digits: Array(6).fill().map(() => ({ value: '' })),

                init() {
                    console.log('OTP Form initialized');
                    this.startCountdown();
                },

                startCountdown() {
                    console.log('Starting countdown');
                    this.countdown = 60;
                    const timer = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(timer);
                        }
                    }, 1000);
                },

                async resendOtp() {
                    this.resending = true;
                    try {
                        const response = await fetch('{{ route("admin.auth.resend-otp") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (response.ok) {
                            this.startCountdown();
                            // Show success message
                            const toast = document.createElement('div');
                            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
                            toast.textContent = 'Verification code sent successfully!';
                            document.body.appendChild(toast);
                            setTimeout(() => toast.remove(), 3000);
                        } else {
                            throw new Error('Failed to resend OTP');
                        }
                    } catch (error) {
                        // Show error message
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
                        toast.textContent = 'Failed to resend code. Please try again.';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                    } finally {
                        this.resending = false;
                    }
                },

                handleInput(index, event) {
                    const value = event.target.value;
                    console.log('Input:', index, value);

                    // Only allow numbers
                    if (!/^\d*$/.test(value)) {
                        event.target.value = '';
                        this.digits[index].value = '';
                        return;
                    }

                    this.digits[index].value = value;
                    console.log('Current OTP:', this.getFullOtp());

                    // Move to next input if a digit is entered
                    if (value.length === 1 && index < 5) {
                        // Find all input fields inside the parent (OTP fields container)
                        const otpInputs = event.target.parentElement.querySelectorAll('input[type="text"]');
                        if (otpInputs && otpInputs[index + 1]) {
                            otpInputs[index + 1].focus();
                        }
                    }
                },

                handleKeydown(index, event) {
                    if (event.key === 'Backspace' && !this.digits[index].value && index > 0) {
                        // Move to previous input on backspace
                        const prevInput = event.target.parentElement.children[index - 1];
                        if (prevInput) {
                            prevInput.focus();
                            this.digits[index - 1].value = '';
                        }
                    }
                },

                handlePaste(event) {
                    event.preventDefault();
                    const paste = (event.clipboardData || window.clipboardData).getData('text');
                    const otpDigits = paste.replace(/\D/g, '').slice(0, 6);

                    for (let i = 0; i < otpDigits.length && i < 6; i++) {
                        this.digits[i].value = otpDigits[i];
                    }

                    // Focus the next empty field or the last field
                    const nextEmptyIndex = Math.min(otpDigits.length, 5);
                    const inputs = event.target.parentElement.children;
                    if (inputs[nextEmptyIndex]) {
                        inputs[nextEmptyIndex].focus();
                    }
                },

                getFullOtp() {
                    const otp = this.digits.map(digit => digit.value).join('');
                    console.log('Full OTP:', otp, 'Length:', otp.length);
                    return otp;
                },

                get isOtpComplete() {
                    const complete = this.getFullOtp().length === 6;
                    console.log('OTP Complete:', complete);
                    return complete;
                }
            }
        }
    </script>

</body>
</html>
