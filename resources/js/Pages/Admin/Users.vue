<script setup>
import dayjs from "dayjs";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import {useToast} from "@/useToast";
import axios from "axios";

const {showToast} = useToast();

const props = defineProps(['users']);

const deleteUser = async (userId) => {
    if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
        try {
            await axios.post(route('users.destroy', {user: userId}));
            showToast("Пользователь успешно удален!", "success");
            props.users.data = props.users.data.filter(user => user.id !== userId);
        } catch (error) {
            showToast("Ошибка при удалении пользователя.", "error");
        }
    }
};
</script>

<template>
    <Head title="Пользователи"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <h3 class="mt-8 mb-4 text-lg font-medium text-gray-900">Список всех пользователей</h3>
            <div v-if="users.data && users.data.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Имя
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Роль
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Почта
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Дата регистрации
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="users.id">
                            <td class="px-3 py-2 whitespace-nowrap">{{ user.id }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ user.name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span v-for="(role, index) in user.roles" :key="index">
                                  <span v-if="role === 'manager'">
                                      Менеджер
                                  </span>
                                  <span v-else-if="role === 'director'">
                                      Директор
                                  </span>
                                    <span v-if="index < user.roles.length - 1">, </span>
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ user.email }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ user.created_at ? dayjs(user.created_at).format('DD.MM.YYYY') : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-800">Удалить
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :items="users"/>
            </div>
            <div v-else class="text-gray-500">
                Ничего не найдено
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
