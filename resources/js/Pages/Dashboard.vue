<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import NavLink from "@/Components/NavLink.vue";
</script>

<template>
    <Head title="Панель" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Панель управления</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="$page.props.auth.role === 'manager' && !$page.props.auth.director_id" class="p-6 text-gray-900">На данный момент вам недоступен функционал для работы с CRM системой,
                        <br>
                        подайте заявку на прикрепление к вашему директору на
                        <NavLink :href="route('collaboration.send-request')">специальной странице</NavLink>
                    </div>
                    <div v-else class="p-6 text-gray-900">Вы вошли!</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
