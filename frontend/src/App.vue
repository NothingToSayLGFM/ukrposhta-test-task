<template>
    <main class="page">
        <div class="flex">
            <n-input
                v-model:value="query"
                placeholder="Search by city or office"
            />
            <n-button type="primary" @click="toggleModal"> Create </n-button>
        </div>
        <n-data-table
            :columns="columns"
            :data="items"
            :loading="loading"
            :rowProps="rowProps"
            class="section"
        />
        <n-pagination
            v-model:page="page"
            :page-count="pagination?.last_page"
            @update:page="load"
        />
    </main>
    <CreateModal
        v-model:show="showCreateModal"
        @create="handleCreate"
        @close="toggleModal"
    />
    <DetailModal
        v-model:show="showDetailModal"
        :data="selectedItem"
        @close="closeDetails"
    />
</template>
<script setup lang="ts">
import { ref, onMounted, shallowRef, h, resolveComponent } from "vue";
import { watchDebounced } from "@vueuse/core";
import {
    searchPostIndexes,
    deletePostIndex,
    createPostIndex,
    getPostIndexById,
} from "@/api/postIndexes";
import type { IPostIndexData, IPaginationData } from "@/types";
import CreateModal from "@/components/CreateModal.vue";
import DetailModal from "@/components/DetailModal.vue";

const NButton = resolveComponent("NButton");

const columns = [
    { title: "Post Code", key: "post_code" },
    { title: "Region", key: "region" },
    { title: "District Old", key: "district_old" },
    { title: "District New", key: "district_new" },
    { title: "City", key: "city" },
    { title: "Post Office", key: "post_office" },
    {
        title: "Actions",
        key: "actions",
        render(row: IPostIndexData) {
            return h(
                NButton,
                {
                    type: "error",
                    size: "small",
                    onClick: async (e) => {
                        e.stopPropagation();
                        await handleDelete(row.post_code);
                    },
                },
                { default: () => "Delete" },
            );
        },
    },
];

const items = ref<IPostIndexData[]>([]);
const pagination = ref<IPaginationData | null>(null);

const loading = ref(false);

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedItem = ref<IPostIndexData | null>(null);

const page = ref(1);
const query = shallowRef("");

async function load() {
    loading.value = true;
    try {
        const { data, meta } = await searchPostIndexes({
            page: page.value,
            q: query.value,
        });
        items.value = data;
        pagination.value = meta;
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id: string) {
    try {
        await deletePostIndex(id);
        await load();
    } catch (err) {
        console.log(err);
    }
}

async function handleCreate(form: IPostIndexData) {
    try {
        await createPostIndex(form);
        toggleModal();
        await load();
    } catch (err) {
        console.log(err);
    }
}

async function showDetail(id: string) {
    try {
        const { data } = await getPostIndexById(id);
        selectedItem.value = data;
        showDetailModal.value = true;
    } catch (err) {
        console.log(err);
    }
    showDetailModal.value = true;
}

const closeDetails = () => {
    showDetailModal.value = false;
};

const toggleModal = () => {
    showCreateModal.value = !showCreateModal.value;
};

function rowProps(row: RowData) {
    return {
        style: "cursor: pointer;",
        onClick: () => {
            showDetail(row.post_code);
        },
    };
}

onMounted(async () => {
    await load();
});

watchDebounced(
    query,
    async () => {
        await load();
    },
    { debounce: 1000, maxWait: 5000 },
);
</script>
