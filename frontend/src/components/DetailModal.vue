<template>
    <n-modal>
        <n-card style="width: 500px" title="Post Index Details">
            <n-space vertical>
                <n-input
                    v-model:value="data.post_code"
                    placeholder="Post Code"
                    disabled
                />
                <n-input
                    v-model:value="data.region"
                    placeholder="Region"
                    disabled
                />
                <n-input
                    v-model:value="data.district_old"
                    placeholder="District Old"
                    disabled
                />
                <n-input
                    v-model:value="data.district_new"
                    placeholder="District New"
                    disabled
                />
                <n-input
                    v-model:value="data.city"
                    placeholder="City"
                    disabled
                />
                <n-input
                    v-model:value="data.post_office"
                    placeholder="Post Office"
                    disabled
                />

                <n-space justify="end">
                    <n-button @click="close">Close</n-button>
                </n-space>
            </n-space>
        </n-card>
    </n-modal>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import type { IPostIndexData } from "@/types";

const props = defineProps<{
    data: IPostIndexData;
}>();

const emit = defineEmits<{
    (e: "close"): void;
}>();

const close = () => {
    emit("close");
};
</script>
