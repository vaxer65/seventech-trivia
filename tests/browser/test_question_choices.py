"""Measure the authored catalog with the actual player option renderer and font."""

import json
from pathlib import Path
import subprocess

from browser_test_case import BrowserTestCase


class QuestionChoiceTest(BrowserTestCase):
    def test_all_1600_choices_fit_one_complete_line_at_320px(self):
        root = Path(__file__).resolve().parents[2]
        result = subprocess.run(
            [
                "php",
                "-r",
                "$questions=[]; foreach (['etapa1_faciles.php','etapa2_medias.php','etapa3_dificiles.php','muerte_subita.php'] as $file) { foreach (require 'database/seeders/data/'.$file as $q) { $questions[]=['id'=>count($questions)+1,'enunciado'=>$q['enunciado'],'opcion_a'=>$q['opcion_a'],'opcion_b'=>$q['opcion_b'],'opcion_c'=>$q['opcion_c'],'opcion_d'=>$q['opcion_d']]; }} echo json_encode($questions, JSON_THROW_ON_ERROR);",
            ],
            cwd=root,
            capture_output=True,
            text=True,
            check=True,
        )
        questions = json.loads(result.stdout)
        self.assertEqual(len(questions), 400)
        payload = {
            "autenticado": True,
            "estado_juego": "en_curso",
            "equipo": {"nombre_equipo": "Local fixture", "puntaje_total": 0},
            "pregunta": questions[0],
            "nivel": {"nombre": "Local stage", "tiempo_limite_seg": 300},
            "progreso_fase": "1 / 5",
        }
        self.context.route("**/api/**", lambda route: route.fulfill(json=payload))
        self.page.set_viewport_size({"width": 320, "height": 568})
        self.visit("/jugar")
        self.page.locator("#vista-pregunta").wait_for(state="visible")
        self.page.evaluate("document.fonts.ready")
        measurements = self.page.evaluate(
            """questions => {
                const failures = [];
                let checked = 0;
                for (const question of questions) {
                    renderizarNuevaPregunta(question, {nombre:'Fixture',tiempo_limite_seg:300}, '1 / 5');
                    for (const button of document.querySelectorAll('.btn-opcion')) {
                        const text = button.lastElementChild;
                        const range = document.createRange();
                        range.selectNodeContents(text);
                        const rects = Array.from(range.getClientRects());
                        const style = getComputedStyle(button);
                        const available = button.clientWidth - parseFloat(style.paddingLeft)
                            - parseFloat(style.paddingRight) - button.firstElementChild.offsetWidth
                            - parseFloat(style.columnGap);
                        if (rects.length !== 1 || rects.some(r => r.width > available + 1)) {
                            failures.push({id:question.id,text:text.textContent,available,lines:rects.length});
                        }
                        checked++;
                    }
                }
                return {checked,failures};
            }""",
            questions,
        )
        self.assertEqual(measurements["checked"], 1600)
        self.assertEqual(measurements["failures"], [])
