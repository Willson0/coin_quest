<script>
import axios from 'axios'
import config from '@/config.json'
import {endLoading, getFileIcon, startLoading, toLink} from "@/utils.js";
export default {
    name: "LessonView",
    data () {
        return {
            lesson: {},
            answers: [],
            lessonNumber: -1,
            result: null,
            isCourseRestart: false,
            realResult: null,
            config: config,
        }
    },
    methods: {
        getFileIcon,
        toLink,
        openUrl (url) {
            window.Telegram.WebApp.openLink(url);
        },
        async nextQuestion () {
            if (this.answers[this.lessonNumber] === undefined) return;
            else if (this.lessonNumber === this.lesson.questions.length - 1) {
                startLoading("loading_lesson");
                await this.sendData();
            }
            else this.lessonNumber++;
        },
        async sendData () {
            await axios.post(config.backend + "lesson/" + this.$route.query.id + "/check", {
                initData: window.Telegram.WebApp.initData,
                answers: this.answers
            }).then((response) => {
                this.lessonNumber = this.lesson?.questions.length;

                let realResult = response.data.points;
                this.realResult = realResult;
                this.result = 0;
                endLoading("loading_lesson");
                setTimeout(() => {
                    var interval = setInterval(() => {
                        if (realResult === this.result) {
                            clearInterval(interval);
                            let title = this.$refs.endPoint;
                            title.parentNode.style.transform = "translate(-50%, -50%)";
                            title.querySelector("span").style.opacity = "1";

                            document.querySelectorAll('.lesson_test_result>button, .lesson_test_result_description').forEach((item) => {
                                item.style.opacity = "1";
                            });
                            if (this.$refs.end_svg) this.$refs.end_svg.style.opacity = "1";
                        }
                        else this.result += 1;
                    }, 10);
                }, 200);

                if (this.lesson.count_tries > 0 && this.result < 50 && this.user.courses.find((course) => course.id === this.lesson.course_id)
                    .lessons.find((lesson) => lesson.id === this.lesson.id).user_count_tries === this.lesson.count_tries) {
                    this.isCourseRestart = true;
                }
                axios.post(config.backend + "auth/profile", {
                    "initData": window.Telegram.WebApp.initData,
                }).then((response) => {
                    let user = response.data;
                    user.courses.forEach(course => {
                        const lessons = course.lessons;
                        const total = lessons.length;
                        const completed = lessons.filter(lesson =>
                            // lesson.user_points !== null && lesson.user_points >= 50
                            lesson.user_points !== null
                        ).length;
                        course.progress = total > 0 ? Math.round((completed / total) * 100) : 0;
                    });
                    this.$store.dispatch("updateUser", user);
                })
            });
        },
        restart () {
            this.answers = [];
            this.lessonNumber = -1;
        },
        prevQuestion () {
            this.lessonNumber--;
        },
        download(url) {
            const a = document.createElement('a');
            a.href = url;
            a.download = url.split('/').pop();
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
    },
    async mounted () {
        await axios.post(config.backend + "lesson/" + this.$route.query.id, {
            initData: window.Telegram.WebApp.initData,
        }).then((response) => {
            this.lesson = response.data;
            endLoading("loading_lesson");
        })
    },
    unmounted () {
        window.Telegram.WebApp.BackButton.offClick(this.prevQuestion);
        window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
    },
    watch: {
        lessonNumber (oldValue, newValue) {
            if (this.lessonNumber === 0) {
                window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
                window.Telegram.WebApp.BackButton.onClick(this.prevQuestion);
            }
            if (this.lessonNumber === -1 || this.lessonNumber === this.lesson.questions.length) {
                window.Telegram.WebApp.BackButton.offClick(this.prevQuestion);
                window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
            }
            if (this.lessonNumber === -1 || this.lessonNumber === 0) {
                let oldBlock = this.lessonNumber === 0 ? this.$refs.theory : this.$refs.test;
                let newBlock = this.lessonNumber === 0 ? this.$refs.test : this.$refs.theory;

                oldBlock.style.opacity = "0";
                oldBlock.addEventListener("transitionend", () => {
                    oldBlock.style.display = "none";
                    newBlock.style.display = "";
                    newBlock.style.opacity = "0";
                    requestAnimationFrame( () => {
                        newBlock.style.opacity = "1";
                    })
                }, {once: true});
            }
        }
    }
}
</script>

<template>
    <div class="loading loading_lesson"></div>
    <div class="lesson_theory" ref="theory">
        <div class="lesson_theory_title">{{ lesson.title }}</div>
        <div class="lesson_theory_count">{{ lesson.oldResult ?? 0 }} / 100 баллы</div>
        <div class="lesson_theory_description">{{ lesson.description }}</div>
        <div class="lesson_theory_videos">
            <div class="lesson_theory_videos_title">Материалы</div>
            <div class="lesson_theory_videos_list">
                <div @click="download(config.storage + lesson.file)">
<!--                    <svg xmlns="http://www.w3.org/2000/svg" width="33" height="33" viewBox="0 0 33 33" fill="none">-->
<!--                        <path d="M8.2824 32.5H24.7144C29.012 32.5 32.4968 29.0152 32.4968 24.7176V8.2824C32.5 3.9944 29.028 0.5128 24.74 0.5H8.26C3.972 0.5128 0.5 3.9944 0.5 8.2824V24.7144C0.5 29.0152 3.9848 32.5 8.2824 32.5Z" fill="#100943"/>-->
<!--                        <path d="M24.74 0.5H16.5C16.5 9.3352 23.6648 16.5 32.5 16.5V8.2824C32.5 3.9944 29.028 0.5128 24.74 0.5Z" fill="#ED143B"/>-->
<!--                        <path d="M20.2664 15.7704H10.8136V12.0296H20.2664C20.82 12.0296 21.204 12.1256 21.396 12.2952C21.588 12.4648 21.7096 12.7752 21.7096 13.2296V14.5736C21.7096 15.0536 21.5912 15.364 21.396 15.5336C21.204 15.7 20.82 15.7704 20.2664 15.7704ZM20.916 8.5H6.80396V24.5H10.8136V19.2936H18.2024L21.7064 24.5H26.196L22.3304 19.2712C23.7544 19.06 24.3944 18.6216 24.9224 17.9048C25.4504 17.1848 25.716 16.0328 25.716 14.5V13.3C25.716 12.388 25.62 11.668 25.4504 11.1176C25.2808 10.5672 24.996 10.0872 24.5864 9.6552C24.1544 9.2456 23.6744 8.9608 23.0984 8.7688C22.5224 8.596 21.8024 8.5 20.916 8.5Z" fill="white"/>-->
<!--                    </svg>-->
                    <i :class="`fas ${getFileIcon(lesson.file)}`"></i>
                </div>
                <div @click="openUrl(lesson.videos.vk)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="33" height="33" viewBox="0 0 33 33" fill="none">
                        <mask id="mask0_106_4026" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="33" height="33">
                            <path d="M32.5 0.5H0.5V32.5H32.5V0.5Z" fill="white"/>
                        </mask>
                        <g mask="url(#mask0_106_4026)">
                            <path d="M0.5 12.1992C0.5 2.5672 2.564 0.5 12.1992 0.5H20.8008C30.4328 0.5 32.5 2.564 32.5 12.1992V20.8008C32.5 30.4328 30.436 32.5 20.8008 32.5H12.1992C2.5672 32.5 0.5 30.436 0.5 20.8008V12.1992Z" fill="#0077FF"/>
                            <path d="M17.6935 32.5H20.7431C30.1959 32.5 32.3975 30.532 32.4935 21.4664L32.4967 20.8008V12.1992C32.4967 11.956 32.4967 11.7192 32.4935 11.4824C32.3879 2.4616 30.1799 0.5 20.7431 0.5H17.6935C8.01354 0.5 5.93994 2.5672 5.93994 12.1992V20.8008C5.93994 30.4328 8.01354 32.5 17.6935 32.5Z" fill="#FF2B42"/>
                            <path d="M23.0792 13.7318C24.6952 14.6662 25.5048 15.1334 25.7768 15.7414C26.0136 16.2726 26.0136 16.8806 25.7768 17.4118C25.5048 18.0198 24.6952 18.487 23.0792 19.4214L18.6472 21.9814C17.028 22.919 16.2184 23.383 15.556 23.3158C14.9768 23.255 14.4488 22.951 14.1096 22.4806C13.7192 21.9398 13.7192 21.0086 13.7192 19.1398V14.0198C13.7192 12.1542 13.7192 11.2198 14.1096 10.679C14.452 10.2086 14.9768 9.90465 15.556 9.84385C16.2216 9.77345 17.028 10.2406 18.6472 11.175L23.0792 13.7318Z" fill="white"/>
                        </g>
                    </svg>
                </div>
                <div @click="openUrl(lesson.videos.youtube)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="47" height="33" viewBox="0 0 47 33" fill="none">
                        <path d="M23.4759 32.5C23.4759 32.5 37.8861 32.5 41.4605 31.54C43.473 30.996 44.9866 29.428 45.5178 27.492C46.5 23.94 46.5 16.468 46.5 16.468C46.5 16.468 46.5 9.044 45.5178 5.524C44.9866 3.54 43.473 2.004 41.4605 1.476C37.8861 0.5 23.4759 0.5 23.4759 0.5C23.4759 0.5 9.09783 0.5 5.53955 1.476C3.55916 2.004 2.01348 3.54 1.44995 5.524C0.5 9.044 0.5 16.468 0.5 16.468C0.5 16.468 0.5 23.94 1.44995 27.492C2.01348 29.428 3.55916 30.996 5.53955 31.54C9.09783 32.5 23.4759 32.5 23.4759 32.5Z" fill="#FF0033"/>
                        <path d="M30.638 16.5002L18.7415 9.7002V23.3002L30.638 16.5002Z" fill="white"/>
                    </svg>
                </div>
            </div>
        </div>
        <button @click="lessonNumber = 0">Я все изучил</button>
    </div>
    <div class="lesson_test" style="display: none" ref="test">
        <div class="lesson_test_background" v-if="lesson.count_tries > 0 && result !== null" :class="{'active': realResult >= 50}"></div>
        <div class="lesson_test_question_container" v-if="lessonNumber >= 0 && lessonNumber !== lesson.questions?.length">
            <div class="lesson_test_title">
                <div>{{ lesson.count_tries > 0 ? 'Экзамен' : 'Тест по теме урока'}}</div>
                <div>{{ Math.min(lessonNumber+1, lesson.questions?.length) }} / {{ lesson.questions?.length }}</div>
            </div>
            <div class="lesson_test_description" v-if="lesson.count_tries > 0">Наберите больше 50 баллов для прохождения теста. У вас есть 2 попытки, после двух неудач пройдите всю тему заново</div>
            <div class="theme_progress_bar">
                <div class="theme_progress_bar_fill" :style="{'width': (lesson.questions?.length === 0 ? 100 : Math.round(((lessonNumber+1) / lesson.questions?.length) * 100)) + '%'}"></div>
                <div class="theme_progress_bar_locked"></div>
            </div>
            <div class="lesson_test_question">{{ lesson.questions[lessonNumber].question }}</div>
            <div class="lesson_test_answers">
                <div v-for="(answ, key) in lesson.questions?.[lessonNumber]?.answers"
                    :class="{'active': answers[lessonNumber] === key}" @click="answers[lessonNumber] = key">
                    {{ answ }}
                </div>
            </div>
            <button @click="nextQuestion()">{{ lessonNumber === lesson.questions?.length - 1 ? 'Узнать результат' : 'Ответить' }}</button>
        </div>
        <div class="lesson_test_result" v-else-if="lessonNumber === lesson.questions?.length">
            <div class="lesson_test_result_title_container">
                <div ref="end_svg" style="opacity: 0" v-if="lesson.count_tries > 0" class="lesson_test_result_svg">
                    <svg v-if="realResult < 50" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100" fill="none">
                        <circle cx="50" cy="50" r="50" fill="#ED8789"/>
                        <path d="M36 36L64 64" stroke="#AF0003" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M64 36L36 64" stroke="#AF0003" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100" fill="none">
                        <circle cx="50" cy="50" r="50" fill="#99E981"/>
                        <path d="M63.9999 36L48.3338 64L36 51.2727" stroke="#1E8100" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <div ref="endPoint" class="lesson_test_result_title">{{ result }}<span style="opacity:0;">Баллов</span></div>
                <div style="opacity:0;" class="lesson_test_result_description" v-if="lesson.count_tries === 0">Результат засчитан!</div>
                <div class="lesson_test_result_description" style="opacity:0;"  v-else>{{ result >= 50 ? 'Вы успешно прошли тему!' : isCourseRestart ? 'Придется пройти тему заново!' : 'У вас есть еще одна попытка' }}</div>
            </div>
            <button v-if="isCourseRestart" style="opacity:0;" @click="toLink('theme', lesson.course_id)">Начать курс заново</button>
            <button v-else-if="result < 50" style="opacity:0;" @click="restart()">Пройти еще раз</button>
            <button v-else style="opacity:0;" @click="toLink('theme', lesson.course_id)">Вернуться</button>
        </div>
    </div>
</template>

<style scoped>

</style>