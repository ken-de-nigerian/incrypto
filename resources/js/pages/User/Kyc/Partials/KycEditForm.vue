<script setup lang="ts">
    import { useForm } from '@inertiajs/vue3';
    import { IdCardIcon, Files, CarIcon, LightbulbIcon, LandmarkIcon, FileTextIcon, Paperclip } from 'lucide-vue-next';
    import { VueTelInput } from 'vue-tel-input';
    import 'vue-tel-input/vue-tel-input.css';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';

    // Define the props passed from the parent page
    const props = defineProps<{
        user: any;
        submission: any;
    }>();

    const hasSubmission = !!props.submission;

    // Inertia's useForm helper for state management
    const form = useForm({
        _method: hasSubmission ? 'PUT' : 'POST', // Use method spoofing for updates
        first_name: props.submission?.first_name || props.user.first_name || '',
        last_name: props.submission?.last_name || props.user.last_name || '',
        email: props.user.email || '',
        phone_number: props.submission?.phone_number || props.user.phone_number || '',
        date_of_birth: props.submission?.date_of_birth || props.user.profile?.date_of_birth || '',
        country: props.submission?.country || props.user.profile?.country || '',
        state: props.submission?.state || props.user.profile?.state || '',
        city: props.submission?.city || props.user.profile?.city || '',
        address: props.submission?.address || props.user.profile?.address || '',
        id_proof_type: props.submission?.id_proof_type || 'national_id',
        id_front_proof: null as File | null,
        id_back_proof: null as File | null,
        address_proof_type: props.submission?.address_proof_type || 'electricity_bill',
        address_front_proof: null as File | null,
        terms: true,
    });

    // Options for the vue-tel-input component
    const vueTelInputOptions = {
        mode: 'international',
        placeholder: 'Enter phone number',
        required: true,
        enabledCountryCode: true,
        enabledFlags: true,
        autocomplete: 'off',
        name: 'telephone',
        maxLen: 25,
        inputOptions: {
            showDialCode: true,
        },
        autoDefaultCountry: true,
    };

    // This function is called when the user selects a country in the phone input
    const handleCountryChange = (country: any) => {
        if (country && country.name) {
            form.country = country.name;
        }
    };

    // File input handler
    const handleFileChange = (event: Event, field: keyof typeof form) => {
        const target = event.target as HTMLInputElement;
        if (target.files && target.files.length > 0) {
            form[field] = target.files[0] as any;
        }
    };

    const submit = () => {
        if (hasSubmission) {
            form.post(route('user.kyc.update', { submission: props.submission.id }), {
                preserveScroll: true,
            });
        } else {
            form.post(route('user.kyc.store'), {
                preserveScroll: true,
            });
        }
    };

    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };
</script>

<template>
    <form @submit.prevent="submit" class="space-y-8">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl">
            <div class="p-6 sm:p-8">
                <h3 class="text-xl font-semibold text-white">Basic Details</h3>
                <p class="mt-1 text-sm text-zinc-400">
                    Please ensure this information matches your identity proof exactly. Details cannot be edited after submission.
                </p>
            </div>

            <div class="p-6 sm:p-8 border-t border-zinc-800">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="space-y-2">
                        <label for="first_name" class="text-sm font-medium">First Name</label>
                        <input id="first_name" v-model="form.first_name" @focus="clearError('first_name')" type="text" placeholder="Enter your first name" :disabled="form.processing" class="input-crypto w-full" />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="space-y-2">
                        <label for="last_name" class="text-sm font-medium">Last Name</label>
                        <input id="last_name" v-model="form.last_name" @focus="clearError('last_name')" type="text" placeholder="Enter your last name" :disabled="form.processing" class="input-crypto w-full" />
                        <InputError :message="form.errors.last_name" />
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium">Email Address</label>
                        <input id="email" v-model="form.email" @focus="clearError('email')" type="email" placeholder="your@email.com" disabled class="block w-full bg-zinc-800/50 border-zinc-700 rounded-lg p-3 text-zinc-400 cursor-not-allowed" />
                    </div>

                    <div class="space-y-2">
                        <label for="phone_number" class="text-sm font-medium">Phone Number</label>
                        <VueTelInput v-model="form.phone_number" @focus="clearError('phone_number')" @country-changed="handleCountryChange" v-bind="vueTelInputOptions" class="vue-tel-input-custom" />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <div class="space-y-2">
                        <label for="date_of_birth" class="text-sm font-medium">Date of Birth</label>
                        <input id="date_of_birth" v-model="form.date_of_birth" @focus="clearError('date_of_birth')" type="date" placeholder="YYYY-MM-DD" :disabled="form.processing" class="input-crypto w-full" />
                        <InputError :message="form.errors.date_of_birth" />
                    </div>
                </div>

                <hr class="border-zinc-700 my-6" />

                <h4 class="font-semibold text-white">Upload Supporting Identity Document</h4>
                <p class="text-zinc-400 text-sm mt-1 mb-6">Ensure the document is valid, not expired, and clearly visible.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <label class="p-4 border rounded-lg cursor-pointer transition-all" :class="form.id_proof_type === 'national_id' ? 'border-lime-400 bg-lime-500/10 ring-2 ring-lime-400' : 'border-zinc-700 bg-zinc-800/50 hover:border-zinc-500'">
                        <input type="radio" v-model="form.id_proof_type" value="national_id" class="hidden" />
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 flex items-center justify-center rounded-md bg-zinc-700 text-lime-400 shrink-0"><IdCardIcon class="w-6 h-6" /></div>
                            <div>
                                <h5 class="font-semibold text-white">National ID</h5>
                                <p class="text-zinc-400 text-sm">Government-Issued ID</p>
                            </div>
                        </div>
                    </label>

                    <label class="p-4 border rounded-lg cursor-pointer transition-all" :class="form.id_proof_type === 'passport' ? 'border-lime-400 bg-lime-500/10 ring-2 ring-lime-400' : 'border-zinc-700 bg-zinc-800/50 hover:border-zinc-500'">
                        <input type="radio" v-model="form.id_proof_type" value="passport" class="hidden" />
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 flex items-center justify-center rounded-md bg-zinc-700 text-lime-400 shrink-0"><Files class="w-6 h-6" /></div>
                            <div>
                                <h5 class="font-semibold text-white">Passport</h5>
                                <p class="text-zinc-400 text-sm">International Passport</p>
                            </div>
                        </div>
                    </label>

                    <label class="p-4 border rounded-lg cursor-pointer transition-all" :class="form.id_proof_type === 'driving_license' ? 'border-lime-400 bg-lime-500/10 ring-2 ring-lime-400' : 'border-zinc-700 bg-zinc-800/50 hover:border-zinc-500'">
                        <input type="radio" v-model="form.id_proof_type" value="driving_license" class="hidden" />
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 flex items-center justify-center rounded-md bg-zinc-700 text-lime-400 shrink-0"><CarIcon class="w-6 h-6" /></div>
                            <div>
                                <h5 class="font-semibold text-white">Driver's License</h5>
                                <p class="text-zinc-400 text-sm">State-Issued License</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Front-side of Proof</label>
                        <input type="file" @change="handleFileChange($event, 'id_front_proof')" @focus="clearError('id_front_proof')" class="block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-zinc-700 file:text-zinc-200 hover:file:bg-zinc-600" />
                        <InputError :message="form.errors.id_front_proof" />
                        <div v-if="submission?.id_front_proof_url" class="mt-2 text-sm text-zinc-400 flex items-center gap-2">
                            <Paperclip class="w-4 h-4" />
                            <a :href="submission.id_front_proof_url" target="_blank" class="hover:underline">View current front-side proof</a>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Back-side of Proof</label>
                        <input type="file" @change="handleFileChange($event, 'id_back_proof')" @focus="clearError('id_back_proof')" class="block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-zinc-700 file:text-zinc-200 hover:file:bg-zinc-600" />
                        <InputError :message="form.errors.id_back_proof" />
                        <div v-if="submission?.id_back_proof_url" class="mt-2 text-sm text-zinc-400 flex items-center gap-2">
                            <Paperclip class="w-4 h-4" />
                            <a :href="submission.id_back_proof_url" target="_blank" class="hover:underline">View current back-side proof</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl">
            <div class="p-6 sm:p-8">
                <h3 class="text-xl font-semibold text-white">Address Details</h3>
                <p class="mt-1 text-sm text-zinc-400">
                    Enter your residential address and upload a matching proof of address.
                </p>
            </div>

            <div class="p-6 sm:p-8 border-t border-zinc-800">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="space-y-2">
                        <label for="country" class="text-sm font-medium">Country</label>
                        <input id="country" v-model="form.country" @focus="clearError('country')" type="text" placeholder="Country" disabled class="block w-full bg-zinc-800/50 border-zinc-700 rounded-lg p-3 text-zinc-400 cursor-not-allowed" />
                        <InputError :message="form.errors.country" />
                    </div>

                    <div class="space-y-2">
                        <label for="state" class="text-sm font-medium">State / Province</label>
                        <input id="state" v-model="form.state" @focus="clearError('state')" type="text" placeholder="Enter your state or province" :disabled="form.processing" class="input-crypto w-full" />
                        <InputError :message="form.errors.state" />
                    </div>

                    <div class="space-y-2">
                        <label for="city" class="text-sm font-medium">City</label>
                        <input id="city" v-model="form.city" @focus="clearError('city')" type="text" placeholder="Enter your city" :disabled="form.processing" class="input-crypto w-full" />
                        <InputError :message="form.errors.city" />
                    </div>

                    <div class="md:col-span-2 lg:col-span-3 space-y-2">
                        <label for="address" class="text-sm font-medium">Street Address</label>
                        <textarea id="address" v-model="form.address" @focus="clearError('address')" rows="3" placeholder="Enter your full street address" :disabled="form.processing" class="input-crypto w-full"></textarea>
                        <InputError :message="form.errors.address" />
                    </div>
                </div>

                <hr class="border-zinc-700 my-6" />

                <h4 class="font-semibold text-white">Upload Proof of Address</h4>
                <p class="text-zinc-400 text-sm mt-1 mb-6">e.g., Utility bill or Bank Statement not older than 3 months.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <label class="p-4 border rounded-lg cursor-pointer transition-all" :class="form.address_proof_type === 'electricity_bill' ? 'border-lime-400 bg-lime-500/10 ring-2 ring-lime-400' : 'border-zinc-700 bg-zinc-800/50 hover:border-zinc-500'">
                        <input type="radio" v-model="form.address_proof_type" value="electricity_bill" class="hidden" />
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 flex items-center justify-center rounded-md bg-zinc-700 text-lime-400 shrink-0">
                                <LightbulbIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h5 class="font-semibold text-white">Utility Bill</h5>
                                <p class="text-zinc-400 text-sm">Water, Gas, or Electricity</p>
                            </div>
                        </div>
                    </label>

                    <label class="p-4 border rounded-lg cursor-pointer transition-all" :class="form.address_proof_type === 'bank_statement' ? 'border-lime-400 bg-lime-500/10 ring-2 ring-lime-400' : 'border-zinc-700 bg-zinc-800/50 hover:border-zinc-500'">
                        <input type="radio" v-model="form.address_proof_type" value="bank_statement" class="hidden" />
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 flex items-center justify-center rounded-md bg-zinc-700 text-lime-400 shrink-0">
                                <LandmarkIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h5 class="font-semibold text-white">Bank Statement</h5>
                                <p class="text-zinc-400 text-sm">Official bank document</p>
                            </div>
                        </div>
                    </label>

                    <label class="p-4 border rounded-lg cursor-pointer transition-all" :class="form.address_proof_type === 'tenancy_agreement' ? 'border-lime-400 bg-lime-500/10 ring-2 ring-lime-400' : 'border-zinc-700 bg-zinc-800/50 hover:border-zinc-500'">
                        <input type="radio" v-model="form.address_proof_type" value="tenancy_agreement" class="hidden" />
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 flex items-center justify-center rounded-md bg-zinc-700 text-lime-400 shrink-0">
                                <FileTextIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h5 class="font-semibold text-white">Tenancy Agreement</h5>
                                <p class="text-zinc-400 text-sm">Signed rental contract</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Upload Proof</label>
                    <input type="file" @change="handleFileChange($event, 'address_front_proof')" @focus="clearError('address_front_proof')" class="block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-zinc-700 file:text-zinc-200 hover:file:bg-zinc-600" />
                    <InputError :message="form.errors.address_front_proof" />
                    <div v-if="submission?.address_front_proof_url" class="mt-2 text-sm text-zinc-400 flex items-center gap-2">
                        <Paperclip class="w-4 h-4" />
                        <a :href="submission.address_front_proof_url" target="_blank" class="hover:underline">View current address proof</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="space-y-2">
                <div class="flex items-center space-x-3 mb-4">
                    <input type="checkbox" v-model="form.terms" @focus="clearError('terms')" id="privacy" class="h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background cursor-pointer" />
                    <label for="privacy" class="text-sm text-muted-foreground leading-relaxed cursor-pointer">
                        I confirm that all the details I have provided are correct.
                    </label>
                </div>
                <InputError class="pb-6" :message="form.errors.terms" />
            </div>

            <ActionButton :processing="form.processing">
                {{ hasSubmission ? 'Resubmit KYC' : 'Submit KYC' }}
            </ActionButton>
        </div>
    </form>
</template>

<style>
    /* Custom styling for vue-tel-input to match your dark theme */
    .vue-tel-input-custom {
        border-radius: 0.5rem;
        border: 1px solid #3f3f46; /* zinc-700 */
    }
    .vue-tel-input-custom:focus-within {
        border-color: #a3e635; /* lime-500 */
        box-shadow: 0 0 0 1px #a3e635;
    }
    .vue-tel-input-custom .vti__input {
        background-color: #27272a; /* zinc-800 */
        border: none;
        border-radius: 0 0.5rem 0.5rem 0;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        color: #ffffff;
        width: 100%;
    }
    .vue-tel-input-custom .vti__input::placeholder {
        color: #71717a; /* zinc-500 */
    }
    .vue-tel-input-custom .vti__dropdown {
        background-color: #27272a; /* zinc-800 */
        border-radius: 0.5rem 0 0 0.5rem;
        border-right: 1px solid #3f3f46; /* zinc-700 */
    }
    .vue-tel-input-custom .vti__dropdown:hover {
        background-color: #3f3f46; /* zinc-700 */
    }
    .vue-tel-input-custom .vti__dropdown-list {
        background-color: #18181b; /* zinc-900 */
        border: 1px solid #3f3f46; /* zinc-700 */
        border-radius: 0.5rem;
        max-height: 200px;
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.5);
    }
    .vue-tel-input-custom .vti__dropdown-item {
        color: #f4f4f5; /* zinc-100 */
        padding: 0.5rem 1rem;
    }
    .vue-tel-input-custom .vti__dropdown-item:hover,
    .vue-tel-input-custom .vti__dropdown-item.highlighted {
        background-color: #3f3f46; /* zinc-700 */
    }
</style>
