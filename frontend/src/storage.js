import { createStore } from 'vuex';

const store = createStore({
    state: {
        user: {},
    },
    mutations: {
        setUser(state, newValue) {
            state.user = newValue;
        },
    },
    actions: {
        updateUser ({ commit }, newValue) {
            commit('setUser', newValue);
        },
    },
});

export default store;
