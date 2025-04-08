<script setup>
import { useForm } from "@inertiajs/vue3";
import Message from 'primevue/message';
import Password from 'primevue/password';
import Button from 'primevue/button';
import { Head } from "@inertiajs/vue3";
import Checkbox from 'primevue/checkbox';

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

</script>

<template>

    <Head title="Log in" />
    <div
        class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div
                style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="w-full px-8 py-20 bg-surface-0 dark:bg-surface-900 sm:px-20" style="border-radius: 53px">
                    <div class="mb-8 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 mx-auto mb-8 shrink-0"
                            viewBox="0 0 256 256">
                            <path fill="var(--primary-color)"
                                d="M107.19 159a56 56 0 1 0-46.38 0a91.83 91.83 0 0 0-53.93 38.81a4 4 0 1 0 6.7 4.37a84 84 0 0 1 140.84 0a4 4 0 1 0 6.7-4.37A91.83 91.83 0 0 0 107.19 159M36 108a48 48 0 1 1 48 48a48.05 48.05 0 0 1-48-48m212 95.35a4 4 0 0 1-5.53-1.17A83.81 83.81 0 0 0 172 164a4 4 0 0 1 0-8a48 48 0 1 0-17.82-92.58a4 4 0 1 1-3-7.43a56 56 0 0 1 44 103a91.83 91.83 0 0 1 53.93 38.86a4 4 0 0 1-1.11 5.5" />
                        </svg>
                        <div class="mb-4 text-3xl font-medium text-surface-900 dark:text-surface-0">Welcome to ART FAD
                            System</div>
                        <span class="font-medium text-muted-color">Sign in to continue</span>
                    </div>

                    <form @submit.prevent="submit">
                        <div>
                            <label for="email1"
                                class="block mb-2 text-xl font-medium text-surface-900 dark:text-surface-0">Email</label>
                            <InputText id="email1" type="text" placeholder="Email" class="w-full md:w-[30rem] mb-4"
                                v-model="form.email" />
                            <Message v-if="form.errors.email" class="mb-4" severity="error">{{ form.errors.email }}
                            </Message>

                            <label for="password1"
                                class="block mb-2 text-xl font-medium text-surface-900 dark:text-surface-0">Password</label>
                            <Password id="password1" v-model="form.password" placeholder="Password" :toggleMask="true"
                                class="mb-4" fluid :feedback="false"></Password>
                            <Message v-if="form.errors.password" class="mb-4" severity="error">
                                {{ form.errors.password }}
                            </Message>

                            <div class="flex items-center justify-between gap-8 mt-2 mb-8">
                                <div class="flex items-center">
                                    <Checkbox v-model="form.remember" id="rememberme1" binary class="mr-2"></Checkbox>
                                    <label for="rememberme1">Remember me</label>
                                </div>
                                <span
                                    class="ml-2 font-medium text-right no-underline cursor-pointer text-primary">Forgot
                                    password?</span>
                            </div>
                            <Button type="submit" label="Sign In" class="w-full"></Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
