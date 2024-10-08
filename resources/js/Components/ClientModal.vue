<script setup>
import {defineProps, defineEmits, ref, watch, computed, onMounted} from 'vue';
import Modal from './Modal.vue';
import SecondaryButton from './SecondaryButton.vue';
import dayjs from "dayjs";
import {useForm, usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "@/useToast";

const {showToast} = useToast();

const props = defineProps({
    show: Boolean,
    client: Object,
});

const emit = defineEmits(['close', 'client-updated']);
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
        loadClientSales(newClient.id);
    }
}, {deep: true});

// получения списка всех покупок клиента
const clientSales = ref([]);
const totalSalesByClient = ref(0);
const loadClientSales = async (clientId) => {
    try {
        const response = await axios.get(route('sales.show', clientId));
        clientSales.value = response.data;
        calculateTotalSales();
        sortClientSalesByDate();
    } catch (error) {
        showToast("Ошибка получения покупок клиента: " + error.message, "error");
    }
};

const firstSaleDate = computed(() => {
    return clientSales.value.length > 0 ? dayjs(clientSales.value[0].sale_date).format('DD.MM.YYYY') : '–';
});

// Вычисляемое свойство для общей суммы продаж
const calculateTotalSales = () => {
    totalSalesByClient.value = clientSales.value.reduce((total, sale) => {
        const paidAmount = parseFloat(sale.paid_amount);
        if (!isNaN(paidAmount)) {
            return total + paidAmount;
        }
        return total;
    }, 0);
};

// сортировка продаж по дате убывания
const sortClientSalesByDate = () => {
    if (!clientSales.value || clientSales.value.length === 0) {
        return;
    }
    clientSales.value.sort((a, b) => {
        const dateA = dayjs(a.created_at);
        const dateB = dayjs(b.created_at);
        return dateB.diff(dateA); // Сортировка по убыванию
    });
};

const isEditing = ref(false);
const sourceOptions = ref(null);
const editClient = async () => {
    try {
        isEditing.value = !isEditing.value;
        const responseSourceOptions = await axios.get(route('clients.getSourceOptions'));
        sourceOptions.value = responseSourceOptions.data;
    } catch (error) {
        showToast("Ошибка при получении опций источников: " + error.message, "error");
    }
};

const deleteClient = async () => {
    if (!props.client || !props.client.id) {
        return;
    }
    if (confirm('Вы уверены, что хотите удалить этого клиента/лида?')) {
        formEdit.delete(route('clients.destroy', {id: props.client.id}), {
            onSuccess: () => {
                showToast("Клиент/лид успешно удален!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};

const submitEdit = () => {
    if (!props.client || !props.client.id) {
        return;
    }

    formEdit.put(route('clients.update', {id: props.client.id}), {
        onSuccess: () => {
            isEditing.value = false;
            emit('client-updated', formEdit.data());
            showToast("Данные успешно обновлены!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
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
            <div>
                <div class="mt-3 text-center sm:mt-0 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        <span v-if="client.is_lead === 1">Информация о лид</span>
                        <span v-else>Информация о клиенте</span>
                        <span class="ml-1">
                            <button title="Редактировать" type="button" @click="editClient" class="mt-1">
                                <i class="fa fa-pencil-square-o text-xl text-blue-700" aria-hidden="true"></i>
                            </button>
                        </span>
                        <span v-if="$page.props.auth.role === 'director'" class="ml-3">
                            <button title="Удалить" type="button" @click="deleteClient" class="mt-1">
                                <i class="fa fa-trash text-xl text-red-700" aria-hidden="true"></i>
                            </button>
                        </span>
                    </h3>
                    <div class="mt-2">
                        <div class="flex gap-2 justify-between" v-if="!isEditing">
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
                            <p class="text-sm text-gray-500">
                                <strong v-if="client.is_lead === 0">Дата создания клиента:</strong>
                                <strong v-else>Дата создания лида:</strong>
                                {{ client.created_at ? dayjs(client.created_at).format('DD.MM.YYYY') : '' }}<br>
                                <span v-if="client.is_lead === 0">
                                    <strong>Дата первого посещения:</strong> {{ firstSaleDate }}<br>
                                </span>
                            </p>
                        </div>
                        <form v-else @submit.prevent="submitEdit">
                            <div class="flex flex-row flex-wrap gap-1 items-end">
                                <div class="flex flex-col">
                                    <label for="surname" class="text-sm font-medium text-gray-700">Фамилия</label>
                                    <input type="text" v-model="formEdit.surname"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.surname" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="name" class="text-sm font-medium text-gray-700">Имя<span
                                        class="text-red-600">*</span></label>
                                    <input type="text" required v-model="formEdit.name"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.name" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="patronymic" class="text-sm font-medium text-gray-700">Отчество</label>
                                    <input type="text" v-model="formEdit.patronymic"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.patronymic"
                                                class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col col-span-1 w-32">
                                    <label for="birthdate" class="text-sm font-medium text-gray-700">Дата
                                        рождения</label>
                                    <input type="date" v-model="formEdit.birthdate"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.birthdate" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="workplace" class="text-sm font-medium text-gray-700">Место
                                        работы</label>
                                    <input type="text" v-model="formEdit.workplace"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.workplace" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                                    <input type="text" v-model="formEdit.phone"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.phone" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="email" class="text-sm font-medium text-gray-700">Почта</label>
                                    <input type="text" v-model="formEdit.email"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.email" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="telegram" class="text-sm font-medium text-gray-700">Телеграм</label>
                                    <input type="text" v-model="formEdit.telegram"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.telegram" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="instagram" class="text-sm font-medium text-gray-700">Инстаграм</label>
                                    <input type="text" v-model="formEdit.instagram"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.instagram" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="address" class="text-sm font-medium text-gray-700">Адрес</label>
                                    <input type="text" v-model="formEdit.address"
                                           class="p-1 border border-gray-300 rounded-md"/>
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
                                    <select id="ad_source" v-model="formEdit.ad_source"
                                            class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                                        <option v-if="sourceOptions && sourceOptions.length === 0" value="" disabled>
                                            Ничего нет
                                        </option>
                                        <option v-for="source in sourceOptions"
                                                :value="source.name" :key="source.id">{{ source.name }}
                                        </option>
                                    </select>
                                    <InputError :message="formEdit.errors.ad_source" class="mt-2 text-sm text-red-600"/>
                                </div>
                            </div>
                            <div class="mt-2 mb-4 flex gap-2">
                                <PrimaryButton size="small" type="submit">Сохранить</PrimaryButton>
                                <SecondaryButton size="small" type="button" @click="isEditing = false">Отмена
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>
                    <div v-if="clientSales.length > 0" class="mt-2">
                        <h4 class="text-md font-medium text-gray-700 mt-2">Все продажи на сумму:
                            {{ Number(totalSalesByClient) }} &#8381;</h4>
                        <div class="overflow-x-auto mt-1">
                            <table class="w-full text-xs border-collapse border border-slate-600">
                                <thead>
                                <tr>
                                    <th class="p-1 border border-slate-600 text-left">Дата</th>
                                    <th class="p-1 border border-slate-600 text-left">Вид продажи</th>
                                    <th class="p-1 border border-slate-600 text-left">Вид спорта</th>
                                    <th class="p-1 border border-slate-600 text-left">Тип услуги</th>
                                    <th class="p-1 border border-slate-600 text-left">Тип продукта</th>
                                    <th class="p-1 border border-slate-600 text-left">Длит. абонем.</th>
                                    <th class="p-1 border border-slate-600 text-left">Посещ. в нед.</th>
                                    <th class="p-1 border border-slate-600 text-left">Кол-во тренировок</th>
                                    <th class="p-1 border border-slate-600 text-left">Тренер</th>
                                    <th class="p-1 border border-slate-600 text-left">Категория тренера</th>
                                    <th class="p-1 border border-slate-600 text-left">Начало абонем.</th>
                                    <th class="p-1 border border-slate-600 text-left">Конец абонем.</th>
                                    <th class="p-1 border border-slate-600 text-left">Цена</th>
                                    <th class="p-1 border border-slate-600 text-left">Оплачено</th>
                                    <th class="p-1 border border-slate-600 text-left">Метод оплаты</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="sale in clientSales" :key="sale.id" class="border-b">
                                    <td class="p-1 border border-slate-600">
                                        {{ sale.sale_date ? dayjs(sale.sale_date).format('DD.MM.YY') : '' }}
                                    </td>
                                    <td class="p-1 border border-slate-600">
                                        <span v-if="sale.service_or_product === 'product'">Товар</span>
                                        <span v-else-if="sale.service_or_product === 'service'">Услуга</span>
                                    </td>
                                    <td class="p-1 border border-slate-600">{{ sale.sport_type }}</td>
                                    <td class="p-1 border border-slate-600">
                                        <span v-if="sale.service_type === 'trial'">Пробная</span>
                                        <span v-else-if="sale.service_type === 'group'">Групповая</span>
                                        <span v-else-if="sale.service_type === 'minigroup'">Минигруппа</span>
                                        <span v-else-if="sale.service_type === 'individual'">Индивидуальная</span>
                                        <span v-else-if="sale.service_type === 'split'">Сплит</span>
                                    </td>
                                    <td class="p-1 border border-slate-600">{{ sale.product_type }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.subscription_duration }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.visits_per_week }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.training_count }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.trainer }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.trainer_category }}</td>
                                    <td class="p-1 border border-slate-600">
                                        {{
                                            sale.subscription_start_date ? dayjs(sale.subscription_start_date).format('DD.MM.YY') : ''
                                        }}
                                    </td>
                                    <td class="p-1 border border-slate-600">
                                        {{
                                            sale.subscription_end_date ? dayjs(sale.subscription_end_date).format('DD.MM.YY') : ''
                                        }}
                                    </td>
                                    <td class="p-1 border border-slate-600">{{ sale.cost }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.paid_amount }}</td>
                                    <td class="p-1 border border-slate-600">{{ sale.pay_method }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
