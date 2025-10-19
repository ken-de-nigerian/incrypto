<script setup lang="ts">
    defineProps<{
        user: {
            first_name: string;
            last_name: string;
            email: string;
            phone_number: string | null;
            created_at: string;
            profile: {
                country: string;
            };
        };
    }>();

    const formatDateShort = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };
</script>

<template>
    <div class="card-crypto border rounded-xl p-4">
        <h5 class="text-lg font-semibold text-card-foreground mb-4 border-b border-border pb-2">Account Details</h5>
        <table class="w-full text-sm">
            <tbody>
                <tr>
                    <th class="py-1 text-left font-medium text-muted-foreground w-1/3">Full Name:</th>
                    <td class="py-1 text-right text-foreground">{{ user.first_name }} {{ user.last_name }}</td>
                </tr>

                <tr>
                    <th class="py-1 text-left font-medium text-muted-foreground">Mobile:</th>
                    <td class="py-1 text-right text-foreground">{{ user.phone_number ?? 'N/A' }}</td>
                </tr>

                <tr>
                    <th class="py-1 text-left font-medium text-muted-foreground">E-mail:</th>
                    <td class="py-1 text-right text-foreground truncate max-w-[150px]">{{ user.email }}</td>
                </tr>

                <tr>
                    <th class="py-1 text-left font-medium text-muted-foreground">Country:</th>
                    <td class="py-1 text-right text-foreground">{{ user.profile?.country ?? 'N/A' }}</td>
                </tr>

                <tr>
                    <th class="py-1 text-left font-medium text-muted-foreground">Registered:</th>
                    <td class="py-1 text-right text-foreground text-xs">{{ formatDateShort(user.created_at) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
