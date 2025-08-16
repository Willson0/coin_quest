<script>
import LessonComponent from "@/components/LessonComponent.vue";

export default {
    name: "ThemeView",
    components: {LessonComponent},
    async mounted () {
        this.id = this.$route.query.id;
        this.initCourse();
    },
    data() {
        return {
            gradients: {
                '1': 'linear-gradient(0deg, transparent 0%, #639AFF 100%)',
                '2': 'linear-gradient(0deg, transparent 0%, #63FFCE 100%)',
                '3': 'linear-gradient(0deg, transparent 0%, #F84040FF 100%)',
            },
            id: 0,
            course: {},
        }
    },
    methods: {
        initCourse() {
            if (!this.user.courses) return;
            if (Object.keys(this.course).length) return;
            this.course = this.user.courses.find(c => c.id === Number(this.id));
        },
        getRussianLessons(count) {
            if (count === 1) return 'урок';
            else if (count > 1 && count < 5) return 'урока'
            else return 'уроков';
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
    },
    watch: {
        user () {
            this.initCourse();
        }
    }
}
</script>

<template>
    <div class="theme">
        <div class="theme_background" :style="{background: gradients[course.level]}"></div>
        <div class="theme_level">{{ user.levels?.[course.level] }}</div>
        <div class="theme_title">
            {{ course.title }}
        </div>
        <div class="theme_description">
            {{ course.description }}
        </div>
        <div class="theme_progress">
            <div class="theme_progress_title">
                <div>Пройдите {{ course.lessons?.length }} {{ getRussianLessons(course.lessons?.length) }}</div>
                <div>{{ Math.round(course.progress / 100 * course.lessons?.length) }} / {{ course.lessons?.length }}</div>
            </div>
            <div class="theme_progress_bar">
                <div :style="{width: course.progress + '%'}" class="theme_progress_bar_fill"></div>
                <div class="theme_progress_bar_locked"></div>
            </div>
        </div>
        <div class="theme_lessons">
            <div class="theme_lessons_title">Уроки</div>
            <div class="theme_lessons_main">
                <lesson-component v-for="les in course.lessons" :lesson="les"
                      :is-locked="(les.number !== 1 && ((course.lessons?.find(lesson => lesson.number === les.number-1)?.user_points) ?? 0) < 50)"/>
            </div>
        </div>
<!--        <lesson-component class="theme_exam" tries="2"/>-->
    </div>
</template>

<style scoped>

</style>