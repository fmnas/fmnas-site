<template>
    <form>
        <label>
            Transport date:
            <input v-model="date" type="date">
            <!-- @todo use a nicer date picker -->
        </label>
        <button v-if="date !== savedDate" type="button" @click="save()">Save</button>
    </form>
</template>

<script>
export default {
    name: 'TransportDate',
    data() {
        return {
            date: null,
            savedDate: null,
        };
    },
    methods: {
        save() {
            fetch('/api/config/transport_date', {
                method: 'PUT',
                body: JSON.stringify(this.date),
            }).then(res => {
                if (!res.ok) {
                    throw res;
                }
                this.savedDate = this.date;
            });
        },
    },
    mounted() {
        // @todo Add a loading indicator for transport date editor
        fetch('/api/config/transport_date', {
            method: 'GET',
        }).then(res => {
            if (!res.ok) {
                throw res;
            }
            return res.json();
        }).then(data => {
            this.date = this.savedDate = data;
        });
    },
};
</script>

<style scoped>

</style>