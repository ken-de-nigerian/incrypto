<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        X,
        User as UserIcon,
        Mail as MailIcon,
        Award as AwardIcon,
        TrendingUp as TrendingUpIcon,
        TrendingDown as TrendingDownIcon,
        Shield as ShieldIcon,
        Users as UsersIcon,
        DollarSign as DollarSignIcon,
        Target as TargetIcon,
        Activity as ActivityIcon,
        Calendar as CalendarIcon,
        CheckCircle as CheckCircleIcon,
        XCircle as XCircleIcon,
        Edit as EditIcon,
        Trash2 as Trash2Icon,
        AlertTriangle as AlertTriangleIcon,
        Loader2 as Loader2Icon,
        BarChart3Icon
    } from 'lucide-vue-next';
    import InputError from '@/components/InputError.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';

    interface UserProfile {
        live_trading_balance: number | string;
        demo_trading_balance: number | string;
        trading_status: 'live' | 'demo';
    }

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        profile: UserProfile;
    }

    interface MasterTrader {
        id: number;
        user_id: number;
        expertise: 'Newcomer' | 'Growing talent' | 'High achiever' | 'Expert' | 'Legend';
        risk_score: number | string;
        gain_percentage: number | string;
        copiers_count: number | string;
        commission_rate: number | string | null;
        total_profit: number | string;
        total_loss: number | string;
        is_active: boolean;
        bio: string | null;
        total_trades: number | string;
        win_rate: number | string;
        created_at: string;
        user: User | null;
    }

    const props = defineProps<{
        isOpen: boolean;
        masterTrader: MasterTrader | null;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const activeTab = ref<'overview' | 'performance' | 'edit' | 'settings'>('overview');
    const showDeleteConfirm = ref(false);
    const isDeleting = ref(false);
    const isTogglingStatus = ref(false);
    const isProcessing = ref(false);
    const validationErrors = ref<Record<string, string>>({});
    const currentStatus = ref(false);

    const formData = ref({
        expertise: 'Newcomer',
        risk_score: 5,
        gain_percentage: 0,
        commission_rate: '',
        total_profit: 0,
        total_loss: 0,
        is_active: true,
        bio: '',
        total_trades: 0,
        win_rate: 0
    });

    const expertiseOptions = [
        { value: 'Newcomer', label: 'Newcomer', color: 'gray' },
        { value: 'Growing talent', label: 'Growing Talent', color: 'blue' },
        { value: 'High achiever', label: 'High Achiever', color: 'green' },
        { value: 'Expert', label: 'Expert', color: 'cyan' },
        { value: 'Legend', label: 'Legend', color: 'orange' }
    ];

    const traderName = computed(() => {
        if (!props.masterTrader?.user) return 'Unknown Trader';
        return `${props.masterTrader.user.first_name} ${props.masterTrader.user.last_name}`;
    });

    const traderInitials = computed(() => {
        if (!props.masterTrader?.user) return '';
        const first = props.masterTrader.user.first_name?.charAt(0) || '';
        const last = props.masterTrader.user.last_name?.charAt(0) || '';
        return `${first}${last}`.toUpperCase();
    });

    const getExpertiseColor = (expertise: string) => {
        const colors = {
            'Newcomer': 'bg-gray-100 text-gray-800 border-gray-200',
            'Growing talent': 'bg-blue-100 text-blue-800 border-blue-200',
            'High achiever': 'bg-green-100 text-green-800 border-green-200',
            'Expert': 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'Legend': 'bg-orange-100 text-orange-800 border-orange-200'
        };
        return colors[expertise as keyof typeof colors] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const profitLossRatio = computed(() => {
        if (!props.masterTrader) return { profit: 50, loss: 50 };

        const profit = parseFloat(props.masterTrader.total_profit as string) || 0;
        const loss = parseFloat(props.masterTrader.total_loss as string) || 0;
        const total = profit + loss;

        if (total === 0) return { profit: 50, loss: 50 };

        return {
            profit: Math.round((profit / total) * 100),
            loss: Math.round((loss / total) * 100)
        };
    });

    const formatAmount = (amount: number | string | null | undefined) => {
        if (!amount) return '0.00';
        const num = typeof amount === 'string' ? parseFloat(amount) : amount;
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const loadTraderData = () => {
        if (props.masterTrader) {
            currentStatus.value = props.masterTrader.is_active;

            formData.value = {
                expertise: props.masterTrader.expertise,
                risk_score: Number(props.masterTrader.risk_score),
                gain_percentage: Number(props.masterTrader.gain_percentage),
                commission_rate: props.masterTrader.commission_rate ? String(props.masterTrader.commission_rate) : '',
                total_profit: Number(props.masterTrader.total_profit),
                total_loss: Number(props.masterTrader.total_loss),
                is_active: props.masterTrader.is_active,
                bio: props.masterTrader.bio || '',
                total_trades: Number(props.masterTrader.total_trades),
                win_rate: Number(props.masterTrader.win_rate)
            };
        }
    };

    const validateForm = (): boolean => {
        validationErrors.value = {};
        let isValid = true;

        if (formData.value.risk_score < 1 || formData.value.risk_score > 10) {
            validationErrors.value.risk_score = 'Risk score must be between 1 and 10';
            isValid = false;
        }

        if (formData.value.win_rate < 0 || formData.value.win_rate > 100) {
            validationErrors.value.win_rate = 'Win rate must be between 0 and 100';
            isValid = false;
        }

        return isValid;
    };

    const handleSubmit = () => {
        if (!props.masterTrader || !validateForm()) return;

        isProcessing.value = true;

        router.patch(route('admin.network.update', props.masterTrader.id), {
            ...formData.value,
            commission_rate: formData.value.commission_rate || null
        }, {
            onSuccess: () => {
                currentStatus.value = formData.value.is_active;
                activeTab.value = 'overview';
                emit('close');
            },
            onError: (errors) => {
                validationErrors.value = errors;
            },
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    };

    const toggleStatus = () => {
        if (!props.masterTrader || isTogglingStatus.value) return;

        isTogglingStatus.value = true;

        router.patch(route('admin.network.toggle.status', props.masterTrader.id), {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                currentStatus.value = !currentStatus.value;
                formData.value.is_active = currentStatus.value;
            },
            onFinish: () => {
                isTogglingStatus.value = false;
            }
        });
    };

    const confirmDelete = () => {
        showDeleteConfirm.value = true;
    };

    const handleDelete = () => {
        if (!props.masterTrader || isDeleting.value) return;

        isDeleting.value = true;

        router.delete(route('admin.network.destroy', props.masterTrader.id), {
            onSuccess: () => {
                emit('close');
                showDeleteConfirm.value = false;
            },
            onFinish: () => {
                isDeleting.value = false;
            }
        });
    };

    const handleClose = () => {
        if (!isDeleting.value && !isTogglingStatus.value && !isProcessing.value) {
            showDeleteConfirm.value = false;
            activeTab.value = 'overview';
            validationErrors.value = {};
            emit('close');
        }
    };

    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            activeTab.value = 'overview';
            showDeleteConfirm.value = false;
            validationErrors.value = {};
            loadTraderData();
        } else {
            document.body.style.overflow = '';
        }
    });

    watch(() => props.masterTrader, () => {
        if (props.isOpen && props.masterTrader) {
            loadTraderData();
        }
    });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen && masterTrader"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm sm:p-4"
            >
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="isOpen && masterTrader"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-4xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border"
                    >
                        <div class="px-4 sm:px-6 py-4 border-b border-border bg-muted/30 shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-primary/20 flex-shrink-0 flex items-center justify-center text-xl font-extrabold text-primary relative">
                                        {{ traderInitials }}
                                        <div
                                            class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full border-2 border-card"
                                            :class="currentStatus ? 'bg-green-500' : 'bg-red-500'"
                                        ></div>
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-lg sm:text-xl font-bold text-card-foreground">{{ traderName }}</h2>
                                        <p class="text-sm text-muted-foreground truncate">{{ masterTrader.user?.email }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="handleClose"
                                    :disabled="isDeleting || isTogglingStatus || isProcessing"
                                    class="p-2 hover:bg-muted cursor-pointer rounded-lg disabled:opacity-50"
                                >
                                    <X class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex gap-2 border-b border-border/50 -mb-4 pb-0 overflow-x-auto">
                                <button
                                    @click="activeTab = 'overview'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'overview'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Overview
                                </button>

                                <button
                                    @click="activeTab = 'performance'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'performance'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Performance
                                </button>

                                <button
                                    @click="activeTab = 'edit'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'edit'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Edit
                                </button>

                                <button
                                    @click="activeTab = 'settings'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'settings'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Settings
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto no-scrollbar overscroll-contain px-4 sm:px-6 py-6">
                            <div v-if="activeTab === 'overview'" class="space-y-6 pb-6">
                                <div v-if="!currentStatus" class="p-4 border border-border rounded-lg flex items-center gap-3">
                                    <XCircleIcon class="w-5 h-5 text-red-600 flex-shrink-0" />
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-red-600">Inactive Trader</p>
                                        <p class="text-xs text-red-600">This trader is currently inactive and not visible to users</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="p-4 bg-muted/30 rounded-lg border border-border">
                                        <div class="flex items-center gap-2 mb-2">
                                            <AwardIcon class="w-4 h-4 text-muted-foreground" />
                                            <span class="text-xs font-semibold text-muted-foreground uppercase">Expertise Level</span>
                                        </div>
                                        <span :class="['inline-block text-sm px-3 py-1 rounded-full border font-bold uppercase tracking-wide', getExpertiseColor(masterTrader.expertise)]">
                                            {{ masterTrader.expertise }}
                                        </span>
                                    </div>

                                    <div class="p-4 bg-muted/30 rounded-lg border border-border">
                                        <div class="flex items-center gap-2 mb-2">
                                            <DollarSignIcon class="w-4 h-4 text-muted-foreground" />
                                            <span class="text-xs font-semibold text-muted-foreground uppercase">Commission Rate</span>
                                        </div>
                                        <p class="text-2xl font-bold" :class="!masterTrader.commission_rate || parseFloat(masterTrader.commission_rate as string) === 0 ? 'text-green-600' : 'text-card-foreground'">
                                            {{ !masterTrader.commission_rate || parseFloat(masterTrader.commission_rate as string) === 0 ? 'FREE' : `$${formatAmount(masterTrader.commission_rate)}` }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <ShieldIcon class="w-3.5 h-3.5 text-muted-foreground" />
                                            <p class="text-xs text-muted-foreground font-semibold">Risk Score</p>
                                        </div>
                                        <p class="text-xl font-bold text-card-foreground">{{ masterTrader.risk_score }}/10</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <TrendingUpIcon class="w-3.5 h-3.5 text-muted-foreground" />
                                            <p class="text-xs text-muted-foreground font-semibold">Gain %</p>
                                        </div>
                                        <p class="text-xl font-bold" :class="parseFloat(masterTrader.gain_percentage as string) >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ parseFloat(masterTrader.gain_percentage as string) >= 0 ? '+' : '' }}{{ parseFloat(masterTrader.gain_percentage as string).toFixed(2) }}%
                                        </p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <UsersIcon class="w-3.5 h-3.5 text-muted-foreground" />
                                            <p class="text-xs text-muted-foreground font-semibold">Copiers</p>
                                        </div>
                                        <p class="text-xl font-bold text-card-foreground">{{ masterTrader.copiers_count }}</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <TargetIcon class="w-3.5 h-3.5 text-muted-foreground" />
                                            <p class="text-xs text-muted-foreground font-semibold">Win Rate</p>
                                        </div>
                                        <p class="text-xl font-bold text-card-foreground">{{ parseFloat(masterTrader.win_rate as string).toFixed(1) }}%</p>
                                    </div>
                                </div>

                                <div v-if="masterTrader.bio" class="bg-muted/30 rounded-lg p-4 border border-border">
                                    <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2 flex items-center gap-2">
                                        <UserIcon class="w-4 h-4" />
                                        Bio
                                    </h3>
                                    <p class="text-sm text-muted-foreground leading-relaxed">{{ masterTrader.bio }}</p>
                                </div>

                                <div class="bg-muted/30 rounded-lg p-4 border border-border">
                                    <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <UserIcon class="w-4 h-4" />
                                        User Account
                                    </h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="flex items-center gap-2">
                                            <MailIcon class="w-4 h-4 text-muted-foreground" />
                                            <div>
                                                <p class="text-xs text-muted-foreground">Email</p>
                                                <p class="text-sm font-medium text-card-foreground">{{ masterTrader.user?.email }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <CalendarIcon class="w-4 h-4 text-muted-foreground" />
                                            <div>
                                                <p class="text-xs text-muted-foreground">Member Since</p>
                                                <p class="text-sm font-medium text-card-foreground">{{ formatDate(masterTrader.created_at) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'performance'" class="space-y-6 pb-6">
                                <div class="p-5 bg-primary/5 border border-primary/20 rounded-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-muted-foreground mb-1">Total Trades Executed</p>
                                            <p class="text-4xl font-black text-card-foreground">{{ masterTrader.total_trades }}</p>
                                        </div>
                                        <ActivityIcon class="w-12 h-12 text-primary/30" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="p-5 border border-border rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <TrendingUpIcon class="w-5 h-5 text-green-600" />
                                            <p class="text-sm font-semibold text-green-600">Total Profit</p>
                                        </div>
                                        <p class="text-3xl font-black text-green-600">${{ formatAmount(masterTrader.total_profit) }}</p>
                                    </div>

                                    <div class="p-5 border border-border rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <TrendingDownIcon class="w-5 h-5 text-red-600" />
                                            <p class="text-sm font-semibold text-red-600">Total Loss</p>
                                        </div>
                                        <p class="text-3xl font-black text-red-600">${{ formatAmount(masterTrader.total_loss) }}</p>
                                    </div>
                                </div>

                                <div class="bg-muted/30 rounded-lg p-5 border border-border">
                                    <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <BarChart3Icon class="w-4 h-4" />
                                        Profit vs Loss Distribution
                                    </h3>

                                    <div class="space-y-4">
                                        <div class="w-full h-8 bg-muted rounded-full overflow-hidden flex">
                                            <div
                                                class="h-full bg-green-500 flex items-center justify-center"
                                                :style="{ width: `${profitLossRatio.profit}%` }"
                                            >
                                                <span v-if="profitLossRatio.profit > 15" class="text-xs font-bold text-white">
                                                    {{ profitLossRatio.profit }}%
                                                </span>
                                            </div>
                                            <div class="h-full w-px bg-background"></div>
                                            <div
                                                class="h-full bg-red-500 flex items-center justify-center"
                                                :style="{ width: `${profitLossRatio.loss}%` }"
                                            >
                                                <span v-if="profitLossRatio.loss > 15" class="text-xs font-bold text-white">
                                                    {{ profitLossRatio.loss }}%
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                                <span class="text-muted-foreground">Profit: {{ profitLossRatio.profit }}%</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                <span class="text-muted-foreground">Loss: {{ profitLossRatio.loss }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <p class="text-xs text-muted-foreground mb-1">Risk Level</p>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                                                <div
                                                    class="h-full transition-all"
                                                    :class="masterTrader.risk_score <= 3 ? 'bg-green-500' : masterTrader.risk_score <= 6 ? 'bg-yellow-500' : 'bg-red-500'"
                                                    :style="{ width: `${(masterTrader.risk_score / 10) * 100}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-bold text-card-foreground">{{ masterTrader.risk_score }}/10</span>
                                        </div>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <p class="text-xs text-muted-foreground mb-1">Win Rate</p>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                                                <div
                                                    class="h-full bg-green-500 transition-all"
                                                    :style="{ width: `${masterTrader.win_rate}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-bold text-card-foreground">{{ parseFloat(masterTrader.win_rate as string).toFixed(1) }}%</span>
                                        </div>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-4">
                                        <p class="text-xs text-muted-foreground mb-1">Gain Performance</p>
                                        <p class="text-lg font-bold" :class="parseFloat(masterTrader.gain_percentage as string) >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ parseFloat(masterTrader.gain_percentage as string) >= 0 ? '+' : '' }}{{ parseFloat(masterTrader.gain_percentage as string).toFixed(2) }}%
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'edit'" class="space-y-6 pb-6">
                                <form @submit.prevent="handleSubmit" class="space-y-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                            Expertise Level *
                                        </label>
                                        <CustomSelectDropdown
                                            v-model="formData.expertise"
                                            :options="expertiseOptions"
                                            placeholder="Select Action Type"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                            Risk Score (1-10) *
                                        </label>
                                        <input v-model.number="formData.risk_score" type="number" min="1" max="10" class="w-full px-4 py-3 bg-background border rounded-lg text-sm input-crypto" />
                                        <InputError :message="validationErrors.risk_score" />
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                                Gain Percentage
                                            </label>
                                            <input v-model.number="formData.gain_percentage" type="number" step="0.01" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                                Commission Rate
                                            </label>
                                            <input v-model="formData.commission_rate" type="number" step="0.01" placeholder="Leave empty for free" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Profit</label>
                                            <input v-model.number="formData.total_profit" type="number" step="0.01" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Loss</label>
                                            <input v-model.number="formData.total_loss" type="number" step="0.01" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                                Total Trades
                                            </label>
                                            <input v-model.number="formData.total_trades" type="number" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Win Rate (%)</label>
                                            <input v-model.number="formData.win_rate" type="number" step="0.01" min="0" max="100" class="w-full px-4 py-3 bg-background border rounded-lg text-sm input-crypto" />
                                            <InputError :message="validationErrors.win_rate" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Bio</label>
                                        <textarea v-model="formData.bio" rows="4" placeholder="Enter trader bio..." class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto resize-none"></textarea>
                                    </div>

                                    <div class="flex items-center gap-3 p-4 bg-muted/30 rounded-lg border border-border">
                                        <input v-model="formData.is_active" type="checkbox" id="is_active" class="w-5 h-5 text-primary bg-background border-border rounded cursor-pointer" />
                                        <label for="is_active" class="text-sm font-medium text-card-foreground cursor-pointer flex-1">
                                            Set as Active Trader
                                        </label>
                                    </div>
                                </form>
                            </div>

                            <div v-if="activeTab === 'settings'" class="space-y-6 pb-6">
                                <div class="bg-muted/30 rounded-lg p-5 border border-border">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-1 flex items-center gap-2">
                                                <ActivityIcon class="w-4 h-4" />
                                                Trader Status
                                            </h3>
                                            <p class="text-sm text-muted-foreground">
                                                {{ currentStatus ? 'This trader is currently active and visible to users.' : 'This trader is currently inactive and hidden from users.' }}
                                            </p>
                                        </div>
                                        <button
                                            @click="toggleStatus"
                                            :disabled="isTogglingStatus"
                                            :class="[
                                                'px-4 py-2 rounded-lg font-semibold text-sm transition-all flex items-center gap-2',
                                                currentStatus
                                                    ? 'bg-red-100 text-red-700 hover:bg-red-200 border border-red-200'
                                                    : 'bg-green-100 text-green-700 hover:bg-green-200 border border-green-200',
                                                isTogglingStatus ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                            ]"
                                        >
                                            <Loader2Icon v-if="isTogglingStatus" class="w-4 h-4 animate-spin" />
                                            <template v-else>
                                                <component :is="currentStatus ? XCircleIcon : CheckCircleIcon" class="w-4 h-4" />
                                                {{ currentStatus ? 'Deactivate' : 'Activate' }}
                                            </template>
                                        </button>
                                    </div>
                                </div>

                                <div class="bg-muted/30 rounded-lg p-5 border border-border">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-1 flex items-center gap-2">
                                                <EditIcon class="w-4 h-4" />
                                                Edit Trader Details
                                            </h3>
                                            <p class="text-sm text-muted-foreground">
                                                Update trader information, metrics, and configuration.
                                            </p>
                                        </div>
                                        <button
                                            @click="activeTab = 'edit'"
                                            class="px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 border border-blue-200 rounded-lg font-semibold text-sm transition-all cursor-pointer flex items-center gap-2"
                                        >
                                            <EditIcon class="w-4 h-4" />
                                            Edit
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-lg p-5 border border-border">
                                    <div v-if="!showDeleteConfirm" class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-sm font-semibold text-red-600 mb-1 flex items-center gap-2">
                                                <AlertTriangleIcon class="w-4 h-4" />
                                                Danger Zone
                                            </h3>
                                            <p class="text-sm text-red-600">
                                                Permanently delete this master trader. This action cannot be undone.
                                            </p>
                                        </div>
                                        <button
                                            @click="confirmDelete"
                                            :disabled="isDeleting"
                                            class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded-lg font-semibold text-sm transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <Trash2Icon class="w-4 h-4" />
                                            Delete
                                        </button>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <AlertTriangleIcon class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-red-600 mb-1">Confirm Deletion</h4>
                                                <p class="text-sm text-red-600 mb-3">
                                                    Are you sure you want to delete <strong>{{ traderName }}</strong>? This will:
                                                </p>
                                                <ul class="text-sm text-red-600 space-y-1 mb-4">
                                                    <li>• Remove the trader from the platform</li>
                                                    <li>• Stop all active copy trades</li>
                                                    <li>• Remove access for {{ masterTrader.copiers_count }} copier(s)</li>
                                                    <li>• Cannot be undone</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="flex gap-3">
                                            <button
                                                @click="showDeleteConfirm = false"
                                                :disabled="isDeleting"
                                                class="flex-1 px-4 py-2 bg-white border border-red-200 text-red-700 hover:bg-red-50 rounded-lg font-semibold text-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                @click="handleDelete"
                                                :disabled="isDeleting"
                                                class="flex-1 px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded-lg font-semibold text-sm transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <Loader2Icon v-if="isDeleting" class="w-4 h-4 animate-spin" />
                                                <Trash2Icon v-else class="w-4 h-4" />
                                                {{ isDeleting ? 'Deleting...' : 'Delete' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 sm:px-6 py-4 border-t border-border bg-muted/10 shrink-0 safe-area-pb">
                            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                                <button
                                    v-if="activeTab === 'edit'"
                                    @click="activeTab = 'overview'"
                                    :disabled="isProcessing"
                                    type="button"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg font-semibold hover:bg-muted cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Cancel
                                </button>

                                <button
                                    v-if="activeTab !== 'edit'"
                                    @click="handleClose"
                                    :disabled="isDeleting || isTogglingStatus || isProcessing"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg font-semibold hover:bg-muted cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Close
                                </button>

                                <button
                                    v-if="activeTab === 'edit'"
                                    @click="handleSubmit"
                                    :disabled="isProcessing"
                                    type="button"
                                    :class="[
                                        'w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold inline-flex justify-center items-center gap-2',
                                        isProcessing
                                            ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                            : 'bg-primary/90 text-primary-foreground cursor-pointer'
                                    ]">
                                    <Loader2Icon v-if="isProcessing" class="w-4 h-4 animate-spin" />
                                    <EditIcon v-else class="w-4 h-4" />
                                    {{ isProcessing ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: hsl(var(--muted));
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground) / 0.5);
    }
</style>
