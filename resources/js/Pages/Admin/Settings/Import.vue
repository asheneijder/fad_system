<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head } from "@inertiajs/vue3";

const toast = useToast();
const selectedFile = ref(null);
const isUploading = ref(false);
const dragOver = ref(false);

const onFileSelect = (event) => {
    const file = event.target.files[0] || event.dataTransfer.files[0];
    if (file && ["application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "text/csv"].includes(file.type)) {
        selectedFile.value = file;
    } else {
        toast.add({ severity: "error", summary: "Invalid File", detail: "Please upload a CSV or Excel file.", life: 3000 });
        selectedFile.value = null;
    }
};

const uploadFile = () => {
    if (!selectedFile.value) return;

    isUploading.value = true;
    const formData = new FormData();
    formData.append("file", selectedFile.value);

    router.post("/admin/settings/import", formData, {
        forceFormData: true,
        onSuccess: (page) => {
            toast.add({ severity: "success", summary: "Success", detail: page.props?.flash?.success || "Import completed successfully.", life: 3000 });
            selectedFile.value = null;
        },
        onError: (errors) => {
            toast.add({ severity: "error", summary: "Upload Failed", detail: errors.file || "File upload failed.", life: 3000 });
        },
        onFinish: () => {
            isUploading.value = false;
        },
    });
};

const removeFile = () => {
    selectedFile.value = null;
};
</script>


<template>
    <Head title="Import Users" />
    <AppLayout>
        <div class="upload-container">
            <h2 class="mb-4 text-lg font-semibold">Upload User Data</h2>

            <div class="drop-zone" 
                @dragover.prevent="dragOver = true" 
                @dragleave.prevent="dragOver = false" 
                @drop.prevent="onFileSelect"
                :class="{ 'border-blue-500': dragOver }">
                
                <input type="file" id="fileInput" class="hidden" @change="onFileSelect" accept=".csv,.xls,.xlsx" />
                <label for="fileInput" class="cursor-pointer">
                    <p v-if="!selectedFile">Drag & drop a file here or <span class="text-blue-600">browse</span></p>
                    <p v-else class="text-gray-700 truncate">{{ selectedFile.name }}</p>
                </label>
            </div>

            <div v-if="selectedFile" class="file-info">
                <p class="text-sm text-gray-600">{{ selectedFile.name }}</p>
                <button @click="removeFile" class="text-sm text-red-500 hover:underline">Remove</button>
            </div>

            <button @click="uploadFile" 
                class="upload-btn" 
                :disabled="isUploading || !selectedFile">
                {{ isUploading ? "Uploading..." : "Upload File" }}
            </button>
        </div>
    </AppLayout>
</template>

<style scoped>
.upload-container {
    max-width: 400px;
    margin: 0 auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.drop-zone {
    border: 2px dashed #ccc;
    padding: 30px;
    border-radius: 6px;
    cursor: pointer;
    transition: border-color 0.3s;
}

.drop-zone p {
    font-size: 14px;
    color: #555;
}

.upload-btn {
    background: #007bff;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    width: 100%;
    margin-top: 10px;
    transition: background 0.3s;
}

.upload-btn:hover {
    background: #0056b3;
}

.upload-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.file-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}
</style>
