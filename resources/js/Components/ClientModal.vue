<script setup>
import {defineProps, defineEmits, ref, watch} from 'vue';
import Modal from './Modal.vue';
import SecondaryButton from './SecondaryButton.vue';
import dayjs from "dayjs";
import {useForm, usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    show: Boolean,
    client: Object,
});

const emit = defineEmits(['close', 'client-updated']);
const form = useForm({
    client_id: props?.client?.id,
    user_sender_id: usePage().props.auth.user.id,
    task_description: null,
    task_date: null,
});
const formEdit = useForm({
    id: props?.client?.id,
    surname: props?.client?.surname,
    name: props?.client?.name,
    patronymic: props?.client?.patronymic,
    birthdate: props?.client?.birthdate,
    workplace: props?.client?.workplace,
    phone: props?.client?.phone,
    email: props?.client?.email,
    telegram: props?.client?.telegram,
    instagram: props?.client?.instagram,
    address: props?.client?.address,
    gender: props?.client?.gender,
    ad_source: props?.client?.ad_source,
});
watch(() => props.client, newClient => {
    if (newClient) {
        Object.assign(formEdit, JSON.parse(JSON.stringify(newClient)));
    }
}, { deep: true });

const submit = () => {
    form.post(route('tasks.store'), {
        onSuccess: () => {
            form.reset();
            isEditing.value = false;
        },
    });
};
const isEditing = ref(false);
const editClient = () => {
    isEditing.value = !isEditing.value;
};

const submitEdit = () => {
    if (!props.client || !props.client.id) {
        console.error('Client data is missing or incomplete');
        console.log(props.client)
        return;
    }

    formEdit.put(route('clients.update', { id: props.client.id }), {
        onSuccess: () => {
            isEditing.value = false;
            emit('client-updated', formEdit.data());
        },
    });
};

const closeModal = () => {
    emit('close');
    isEditing.value = false;
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div v-if="client" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="">
                <div class="mt-3 text-center sm:mt-0 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Информация о лид/клиенте
                        <span class="ml-1">
                            <button title="Редактировать" type="button" @click="editClient" class="mt-1">
                                <i class="fa fa-pencil-square-o text-xl" aria-hidden="true"></i>
                            </button>
                        </span>
                    </h3>
                    <div class="mt-2">
                        <div v-if="!isEditing">
                            <p class="text-sm text-gray-500">
                                <strong>Фамилия:</strong> {{ client.surname }}<br>
                                <strong>Имя:</strong> {{ client.name }}<br>
                                <strong>Отчество:</strong> {{ client.patronymic }}<br>
                                <strong>Дата рождения:</strong>
                                {{ client.birthdate ? dayjs(client.birthdate).format('DD.MM.YYYY') : '' }}<br>
                                <strong>Место работы:</strong> {{ client.workplace }}<br>
                                <strong>Телефон:</strong> {{ client.phone }}<br>
                                <strong>Почта:</strong> {{ client.email }}<br>
                                <strong>Телеграм:</strong> {{ client.telegram }}<br>
                                <strong>Инстаграм:</strong> {{ client.instagram }}<br>
                                <strong>Адрес:</strong> {{ client.address }}<br>
                                <strong>Пол:</strong> {{ client.gender === 'male' ? 'М' : 'Ж' }}<br>
                                <strong>Источник:</strong> {{ client.ad_source }}
                            </p>
                        </div>
                        <form v-else @submit.prevent="submitEdit">
                            <div class="flex flex-row flex-wrap gap-1 items-end">
                                <div class="flex flex-col">
                                    <label for="surname" class="text-sm font-medium text-gray-700">Фамилия</label>
                                    <input type="text" v-model="formEdit.surname" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.surname" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="name" class="text-sm font-medium text-gray-700">Имя<span class="text-red-600">*</span></label>
                                    <input type="text" required v-model="formEdit.name" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.name" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="patronymic" class="text-sm font-medium text-gray-700">Отчество</label>
                                    <input type="text" v-model="formEdit.patronymic" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.patronymic" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col col-span-1 w-32">
                                    <label for="birthdate" class="text-sm font-medium text-gray-700">Дата рождения</label>
                                    <input type="date" v-model="formEdit.birthdate" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.birthdate" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="workplace" class="text-sm font-medium text-gray-700">Место работы</label>
                                    <input type="text" v-model="formEdit.workplace" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.workplace" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                                    <input type="text" v-model="formEdit.phone" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.phone" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="email" class="text-sm font-medium text-gray-700">Почта</label>
                                    <input type="text" v-model="formEdit.email" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.email" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="telegram" class="text-sm font-medium text-gray-700">Телеграм</label>
                                    <input type="text" v-model="formEdit.telegram" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.telegram" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="instagram" class="text-sm font-medium text-gray-700">Инстаграм</label>
                                    <input type="text" v-model="formEdit.instagram" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.instagram" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="address" class="text-sm font-medium text-gray-700">Адрес</label>
                                    <input type="text" v-model="formEdit.address" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.address" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col w-14">
                                    <label for="gender" class="text-sm font-medium text-gray-700">Пол</label>
                                    <select v-model="formEdit.gender" class="p-1 border border-gray-300 rounded-md">
                                        <option value="male">М</option>
                                        <option value="female">Ж</option>
                                    </select>
                                    <InputError :message="formEdit.errors.gender" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="ad_source" class="text-sm font-medium text-gray-700">Источник</label>
                                    <input type="text" v-model="formEdit.ad_source" class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.ad_source" class="mt-2 text-sm text-red-600"/>
                                </div>
                            </div>
                            <div class="mt-2 mb-4 flex gap-2">
                                <PrimaryButton size="small" type="submit">Сохранить</PrimaryButton>
                                <SecondaryButton size="small" type="button" @click="isEditing = false">Отмена</SecondaryButton>
                            </div>
                        </form>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="mt-2 flex">
                            <h3 class="text-lg mr-2">Задача на дату</h3>
                            <div class="flex w-32">
                                <input type="date"
                                       v-model="form.task_date"
                                       class="p-0 pl-1 border border-gray-300 rounded-md"
                                />
                            </div>
                        </div>
                        <div class="mt-2">
                            <textarea rows="3"
                                      v-model="form.task_description"
                                      class="w-full p-1 border border-gray-300 rounded-md text-sm"
                                      placeholder="Введите задачу по лиду/клиенту"></textarea>
                        </div>
                        <secondary-button size="small" type="submit">Отправить задачу</secondary-button>
                    </form>
                </div>
            </div>
        </div>
    </Modal>
</template>
