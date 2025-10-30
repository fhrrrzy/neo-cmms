<script setup>
import UserInfo from '@/components/UserInfo.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({ user: Object });

const showLogoutConfirm = ref(false);
const handleLogout = (e) => {
    e.preventDefault();
    showLogoutConfirm.value = true;
};
const confirmLogout = () => {
    showLogoutConfirm.value = false;
    router.flushAll();
};
const cancelLogout = () => {
    showLogoutConfirm.value = false;
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>

    <Dialog v-model="showLogoutConfirm">
        <DialogContent>
            <div class="flex flex-col items-center gap-4 text-center">
                <div class="text-lg font-medium">Confirm Logout</div>
                <div>Are you sure you want to log out?</div>
                <div class="mt-4 flex gap-4">
                    <button
                        class="btn btn-sm btn-primary"
                        @click="confirmLogout"
                    >
                        Yes, Log Out
                    </button>
                    <button
                        class="btn btn-sm btn-outline"
                        @click="cancelLogout"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
