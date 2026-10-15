<script setup >

const props = defineProps({
    title: String,
    breadcrumbs: Array ,
    icon: String,
    text: String
});


</script>

<template>
    <v-card class="bg-lightprimary elevation-0 rounded-md mb-4">
        <div class="px-6 py-3">
            <div class="d-flex justify-space-between align-center">
                <div class="d-flex py-0 align-center">
                    <div>
                        <h3 class="text-h5 mb-1">{{ title }}</h3>
                        <v-breadcrumbs :items="breadcrumbs" class="text-body-2 font-weight-medium pa-0 ml-n1">
                                <template v-slot:divider>
                                    <div class="d-flex align-center">·</div>
                                </template>
                                <template v-slot:title="{ item }">
                                    <span class="text-body-2">{{ item.text }}</span>
                                </template>
                            </v-breadcrumbs>

                    </div>
                </div>
                <div class="d-none py-0 d-lg-block overflow-hidden">
                    <div class="">
                        <img src="@/assets/images/breadcrumb/30years-transparencia.png" alt="breadcrumb" style="max-height:  56px; width: auto;" />
                    </div>
                </div>
            </div>
        </div>
    </v-card>
</template>

<style lang="scss">
.page-breadcrumb {
    .v-toolbar {
        background: transparent;
    }
}
</style>
