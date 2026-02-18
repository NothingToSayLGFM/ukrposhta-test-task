<template>
    <n-modal>
        <n-card style="width: 500px" title="Create Post Index">
            <n-space vertical>
                <n-input
                    v-model:value="form.post_code"
                    placeholder="Post Code"
                />
                <n-input v-model:value="form.region" placeholder="Region" />
                <n-input
                    v-model:value="form.district_old"
                    placeholder="District Old"
                />
                <n-input
                    v-model:value="form.district_new"
                    placeholder="District New"
                />
                <n-input v-model:value="form.city" placeholder="City" />
                <n-input
                    v-model:value="form.post_office"
                    placeholder="Post Office"
                />

                <n-space justify="end">
                    <n-button @click="close">Cancel</n-button>
                    <n-button type="primary" @click="handleCreate"
                        >Create</n-button
                    >
                </n-space>
            </n-space>
        </n-card>
    </n-modal>
</template>

<script setup lang="ts">
import { reactive } from "vue";
import type { IPostIndexData } from "../types";

const emit = defineEmits<{
    (e: "create"): void;
    (e: "close"): void;
}>();

const form = reactive<IPostIndexData>({
    post_code: "",
    region: "",
    district_old: "",
    district_new: "",
    city: "",
    post_office: "",
});

const close = () => {
    emit("close");
};

const handleCreate = () => {
    emit("create", form);
};
</script>
