<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ __('Edit User') }}</h1>
            <p class="pcv-topbar-meta mt-0.5">{{ __('Update user information and role.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="pcv-panel">
            <div class="p-6">
                <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PATCH')

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input 
                            id="name" 
                            name="name" 
                            type="text" 
                            class="mt-1 block w-full" 
                            value="{{ old('name', $user->name) }}" 
                            required 
                            autofocus 
                        />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input 
                            id="email" 
                            name="email" 
                            type="email" 
                            class="mt-1 block w-full" 
                            value="{{ old('email', $user->email) }}" 
                            required 
                        />
                        <x-input-error :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password (leave blank to keep current)')" />
                        <div class="relative">
                            <x-text-input 
                                id="password" 
                                name="password" 
                                type="password" 
                                class="mt-1 block w-full pr-10" 
                                value="{{ old('password') }}" 
                                placeholder="{{ __('Leave blank to keep current password') }}"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                onclick="togglePassword('password')"
                            >
                                <i id="password-eye" class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <div class="relative">
                            <x-text-input 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                type="password" 
                                class="mt-1 block w-full pr-10" 
                                value="{{ old('password_confirmation') }}" 
                                placeholder="{{ __('Confirm new password') }}"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                onclick="togglePassword('password_confirmation')"
                            >
                                <i id="password_confirmation-eye" class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <select 
                            id="role" 
                            name="role" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            required
                        >
                            <option value="">{{ __('Select a role') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('role', $user->role->value) == $role->value ? 'selected' : '' }}>
                                    {{ ucfirst($role->value) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" />
                    </div>

                    @if($user->id === auth()->id())
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            {{ __('You are editing your own profile. Be careful when changing your role or password.') }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end mt-8 space-x-4 border-t pt-6">
                    <a href="{{ route('users.index') }}" class="inline-flex items-center px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white !text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-save mr-2 text-white"></i>
                        {{ __('Update User') }}
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }
    </script>
</x-app-layout>
