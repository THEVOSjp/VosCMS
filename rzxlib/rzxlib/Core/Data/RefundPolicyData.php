<?php
/**
 * RezlyX - 취소/환불 규정 기본 콘텐츠
 * 미용실(Beauty Salon) 기준 샘플 - 운영자가 참고하여 수정 가능
 */

namespace RzxLib\Core\Data;

class RefundPolicyData
{
    /**
     * 언어별 기본 콘텐츠 가져오기
     * @param string $locale 언어 코드
     * @return array ['title' => string, 'content' => string]
     */
    public static function get(string $locale): array
    {
        $method = 'get' . str_replace('-', '_', $locale);
        if (method_exists(self::class, $method)) {
            return self::$method();
        }
        return self::geten();
    }

    /**
     * 지원 언어 목록
     */
    public static function getSupportedLocales(): array
    {
        return ['ko','en','ja','zh_CN','zh_TW','de','es','fr','id','mn','ru','tr','vi'];
    }

    /**
     * 모든 언어 콘텐츠 한번에 가져오기
     */
    public static function getAll(): array
    {
        $result = [];
        foreach (self::getSupportedLocales() as $locale) {
            $result[$locale] = self::get($locale);
        }
        return $result;
    }

    private static function getko(): array
    {
        return [
            'title' => '취소 및 환불 규정',
            'content' => self::koContent(),
        ];
    }

    private static function geten(): array
    {
        return [
            'title' => 'Cancellation & Refund Policy',
            'content' => self::enContent(),
        ];
    }

    private static function getja(): array
    {
        return [
            'title' => 'キャンセル・返金規定',
            'content' => self::jaContent(),
        ];
    }

    private static function getzh_CN(): array
    {
        return [
            'title' => '取消与退款政策',
            'content' => self::zhCNContent(),
        ];
    }

    private static function getzh_TW(): array
    {
        return [
            'title' => '取消與退款政策',
            'content' => self::zhTWContent(),
        ];
    }

    private static function getde(): array
    {
        return [
            'title' => 'Stornierungs- und Erstattungsrichtlinie',
            'content' => self::deContent(),
        ];
    }

    private static function getes(): array
    {
        return [
            'title' => 'Política de Cancelación y Reembolso',
            'content' => self::esContent(),
        ];
    }

    private static function getfr(): array
    {
        return [
            'title' => "Politique d'Annulation et de Remboursement",
            'content' => self::frContent(),
        ];
    }

    private static function getid(): array
    {
        return [
            'title' => 'Kebijakan Pembatalan dan Pengembalian Dana',
            'content' => self::idContent(),
        ];
    }

    private static function getmn(): array
    {
        return [
            'title' => 'Цуцлалт болон буцаан олголтын бодлого',
            'content' => self::mnContent(),
        ];
    }

    private static function getru(): array
    {
        return [
            'title' => 'Политика отмены и возврата',
            'content' => self::ruContent(),
        ];
    }

    private static function gettr(): array
    {
        return [
            'title' => 'İptal ve İade Politikası',
            'content' => self::trContent(),
        ];
    }

    private static function getvi(): array
    {
        return [
            'title' => 'Chính sách Hủy và Hoàn tiền',
            'content' => self::viContent(),
        ];
    }

    // === 콘텐츠 메서드 ===

    private static function koContent(): string
    {
        return <<<'HTML'
<h2>1. 예약 취소 규정</h2>
<p>고객님의 원활한 서비스 이용과 다른 고객님의 예약 기회를 보장하기 위해 아래와 같은 취소 규정을 운영하고 있습니다.</p>
<table>
<thead>
<tr><th>취소 시점</th><th>환불 비율</th></tr>
</thead>
<tbody>
<tr><td>예약 시간 24시간 이전</td><td>100% 전액 환불</td></tr>
<tr><td>예약 시간 12~24시간 전</td><td>50% 환불</td></tr>
<tr><td>예약 시간 12시간 이내</td><td>환불 불가</td></tr>
<tr><td>노쇼 (No-Show)</td><td>환불 불가</td></tr>
</tbody>
</table>

<h2>2. 노쇼(No-Show) 정책</h2>
<p>사전 연락 없이 예약 시간에 방문하지 않으신 경우 노쇼로 처리됩니다.</p>
<ul>
<li>노쇼 시 결제 금액은 환불되지 않습니다.</li>
<li>반복적인 노쇼 발생 시 향후 예약이 제한될 수 있습니다.</li>
<li>예약 시간 10분 이상 지각 시 시술이 불가능하거나 시술 시간이 단축될 수 있습니다.</li>
</ul>

<h2>3. 예약 변경</h2>
<ul>
<li>예약 시간 24시간 이전: 무료로 1회 변경 가능</li>
<li>예약 시간 24시간 이내: 변경이 불가하며, 취소 후 재예약이 필요합니다.</li>
<li>시술 메뉴 변경은 방문 시 현장에서 가능합니다 (가격 차이 발생 가능).</li>
</ul>

<h2>4. 시술 후 불만족 시</h2>
<ul>
<li>시술 결과에 불만족하신 경우, 시술 후 7일 이내 연락해주시면 무료 재시술을 진행합니다.</li>
<li>재시술은 동일 시술에 한하며, 다른 시술로의 변경은 별도 비용이 발생합니다.</li>
<li>고객의 사전 요청과 다른 결과물에 한해 적용됩니다.</li>
</ul>

<h2>5. 선불 이용권 / 패키지</h2>
<ul>
<li>미사용 잔여 횟수에 대해 환불이 가능하며, 이 경우 이미 사용한 횟수는 정상가로 재계산됩니다.</li>
<li>유효기간이 경과한 이용권은 환불이 불가합니다.</li>
<li>할인 적용된 패키지의 환불 금액은 정상 단가 기준으로 차감 후 잔액을 환불합니다.</li>
</ul>

<h2>6. 환불 처리 기간</h2>
<ul>
<li>카드 결제: 취소 후 3~5 영업일 이내 환불</li>
<li>현금/계좌이체: 환불 요청 후 5~7 영업일 이내 지정 계좌로 입금</li>
<li>포인트 결제: 즉시 포인트로 재적립</li>
</ul>

<h2>7. 기타 사항</h2>
<ul>
<li>천재지변, 긴급 상황 등 불가피한 경우 별도 협의가 가능합니다.</li>
<li>본 규정은 사전 공지 후 변경될 수 있습니다.</li>
<li>문의사항은 전화 또는 메시지로 연락해주세요.</li>
</ul>
HTML;
    }

    private static function enContent(): string
    {
        return <<<'HTML'
<h2>1. Reservation Cancellation Policy</h2>
<p>To ensure smooth service for all customers and fair access to appointments, we operate the following cancellation policy.</p>
<table>
<thead>
<tr><th>Cancellation Timing</th><th>Refund Rate</th></tr>
</thead>
<tbody>
<tr><td>More than 24 hours before appointment</td><td>100% full refund</td></tr>
<tr><td>12–24 hours before appointment</td><td>50% refund</td></tr>
<tr><td>Less than 12 hours before appointment</td><td>No refund</td></tr>
<tr><td>No-Show</td><td>No refund</td></tr>
</tbody>
</table>

<h2>2. No-Show Policy</h2>
<p>If you fail to arrive at your scheduled appointment time without prior notice, it will be treated as a no-show.</p>
<ul>
<li>No-show appointments are non-refundable.</li>
<li>Repeated no-shows may result in restrictions on future bookings.</li>
<li>Arriving more than 10 minutes late may result in service refusal or reduced service time.</li>
</ul>

<h2>3. Appointment Changes</h2>
<ul>
<li>More than 24 hours before: One free reschedule is allowed.</li>
<li>Less than 24 hours before: Changes are not permitted; please cancel and rebook.</li>
<li>Service menu changes can be made on-site (price differences may apply).</li>
</ul>

<h2>4. Dissatisfaction After Service</h2>
<ul>
<li>If you are unsatisfied with the results, please contact us within 7 days for a complimentary redo.</li>
<li>Redo is limited to the same service; changing to a different service will incur additional charges.</li>
<li>This applies only when the result differs from your pre-agreed request.</li>
</ul>

<h2>5. Prepaid Packages / Vouchers</h2>
<ul>
<li>Unused sessions are refundable; used sessions will be recalculated at the regular price.</li>
<li>Expired vouchers are non-refundable.</li>
<li>Refund amounts for discounted packages are calculated by deducting used sessions at regular pricing.</li>
</ul>

<h2>6. Refund Processing Time</h2>
<ul>
<li>Card payments: Refunded within 3–5 business days.</li>
<li>Cash/bank transfer: Deposited to your designated account within 5–7 business days.</li>
<li>Points: Instantly re-credited to your account.</li>
</ul>

<h2>7. Additional Notes</h2>
<ul>
<li>Exceptions may be made in cases of natural disasters or emergencies.</li>
<li>This policy may be updated with prior notice.</li>
<li>For inquiries, please contact us by phone or message.</li>
</ul>
HTML;
    }

    private static function jaContent(): string
    {
        return <<<'HTML'
<h2>1. 予約キャンセル規定</h2>
<p>すべてのお客様に円滑なサービスをご提供し、公平な予約機会を確保するため、以下のキャンセル規定を設けております。</p>
<table>
<thead>
<tr><th>キャンセル時期</th><th>返金率</th></tr>
</thead>
<tbody>
<tr><td>予約時間の24時間以上前</td><td>100%全額返金</td></tr>
<tr><td>予約時間の12〜24時間前</td><td>50%返金</td></tr>
<tr><td>予約時間の12時間以内</td><td>返金不可</td></tr>
<tr><td>無断キャンセル（No-Show）</td><td>返金不可</td></tr>
</tbody>
</table>

<h2>2. 無断キャンセル（No-Show）ポリシー</h2>
<p>事前連絡なく予約時間にご来店されなかった場合、無断キャンセルとして処理されます。</p>
<ul>
<li>無断キャンセルの場合、お支払い金額は返金されません。</li>
<li>繰り返しの無断キャンセルは、今後のご予約が制限される場合があります。</li>
<li>予約時間より10分以上遅刻された場合、施術をお断りするか、施術時間が短縮される場合があります。</li>
</ul>

<h2>3. 予約変更</h2>
<ul>
<li>予約時間の24時間以上前：1回まで無料で変更可能</li>
<li>予約時間の24時間以内：変更不可、キャンセル後に再予約が必要です。</li>
<li>施術メニューの変更はご来店時に対応可能です（料金の差額が発生する場合があります）。</li>
</ul>

<h2>4. 施術後のご不満について</h2>
<ul>
<li>施術結果にご不満の場合、施術後7日以内にご連絡いただければ無料でお直しいたします。</li>
<li>お直しは同一施術に限り、別の施術への変更は別途料金が発生します。</li>
<li>お客様の事前のご要望と異なる結果の場合に限り適用されます。</li>
</ul>

<h2>5. 前払い回数券 / パッケージ</h2>
<ul>
<li>未使用分の返金が可能です。使用済み回数は通常料金で再計算されます。</li>
<li>有効期限が切れた回数券の返金はできません。</li>
<li>割引パッケージの返金額は、使用分を通常単価で差し引いた残額となります。</li>
</ul>

<h2>6. 返金処理期間</h2>
<ul>
<li>クレジットカード：キャンセル後3〜5営業日以内に返金</li>
<li>現金/銀行振込：返金申請後5〜7営業日以内にご指定口座へ入金</li>
<li>ポイント：即時ポイント再付与</li>
</ul>

<h2>7. その他</h2>
<ul>
<li>天災・緊急事態等やむを得ない場合は別途ご相談に応じます。</li>
<li>本規定は事前告知の上、変更される場合があります。</li>
<li>ご不明な点はお電話またはメッセージでお問い合わせください。</li>
</ul>
HTML;
    }

    private static function zhCNContent(): string
    {
        return <<<'HTML'
<h2>1. 预约取消规定</h2>
<p>为确保所有顾客享有顺畅的服务体验和公平的预约机会，我们制定了以下取消规定。</p>
<table>
<thead>
<tr><th>取消时间</th><th>退款比例</th></tr>
</thead>
<tbody>
<tr><td>预约时间24小时以前</td><td>100%全额退款</td></tr>
<tr><td>预约时间12~24小时前</td><td>50%退款</td></tr>
<tr><td>预约时间12小时以内</td><td>不予退款</td></tr>
<tr><td>未到店（No-Show）</td><td>不予退款</td></tr>
</tbody>
</table>

<h2>2. 未到店（No-Show）政策</h2>
<p>未提前通知且未按预约时间到店的情况将视为未到店。</p>
<ul>
<li>未到店的已付款项不予退还。</li>
<li>多次未到店可能会限制后续预约。</li>
<li>迟到超过10分钟可能导致无法服务或服务时间缩短。</li>
</ul>

<h2>3. 预约变更</h2>
<ul>
<li>预约时间24小时以前：可免费改期1次。</li>
<li>预约时间24小时以内：无法变更，请取消后重新预约。</li>
<li>服务项目变更可在到店时现场处理（可能产生差价）。</li>
</ul>

<h2>4. 服务后不满意</h2>
<ul>
<li>如对服务效果不满意，请在服务后7天内联系我们，可享受免费重做。</li>
<li>重做仅限同一服务项目，更换其他项目需另外收费。</li>
<li>仅适用于结果与事先约定需求不符的情况。</li>
</ul>

<h2>5. 预付套餐/充值卡</h2>
<ul>
<li>未使用的剩余次数可申请退款，已使用次数将按原价重新计算。</li>
<li>已过期的套餐不予退款。</li>
<li>折扣套餐的退款金额按原价扣除已使用次数后计算。</li>
</ul>

<h2>6. 退款处理时间</h2>
<ul>
<li>信用卡支付：取消后3~5个工作日内退款。</li>
<li>现金/银行转账：申请后5~7个工作日内退至指定账户。</li>
<li>积分支付：即时返还积分。</li>
</ul>

<h2>7. 其他事项</h2>
<ul>
<li>遇不可抗力或紧急情况，可另行协商。</li>
<li>本规定可能会在提前通知后进行修改。</li>
<li>如有疑问，请通过电话或消息联系我们。</li>
</ul>
HTML;
    }

    private static function zhTWContent(): string
    {
        return <<<'HTML'
<h2>1. 預約取消規定</h2>
<p>為確保所有顧客享有順暢的服務體驗和公平的預約機會，我們制定了以下取消規定。</p>
<table>
<thead>
<tr><th>取消時間</th><th>退款比例</th></tr>
</thead>
<tbody>
<tr><td>預約時間24小時以前</td><td>100%全額退款</td></tr>
<tr><td>預約時間12~24小時前</td><td>50%退款</td></tr>
<tr><td>預約時間12小時以內</td><td>不予退款</td></tr>
<tr><td>未到店（No-Show）</td><td>不予退款</td></tr>
</tbody>
</table>

<h2>2. 未到店（No-Show）政策</h2>
<p>未提前通知且未按預約時間到店的情況將視為未到店。</p>
<ul>
<li>未到店的已付款項不予退還。</li>
<li>多次未到店可能會限制後續預約。</li>
<li>遲到超過10分鐘可能導致無法服務或服務時間縮短。</li>
</ul>

<h2>3. 預約變更</h2>
<ul>
<li>預約時間24小時以前：可免費改期1次。</li>
<li>預約時間24小時以內：無法變更，請取消後重新預約。</li>
<li>服務項目變更可在到店時現場處理（可能產生差價）。</li>
</ul>

<h2>4. 服務後不滿意</h2>
<ul>
<li>如對服務效果不滿意，請在服務後7天內聯繫我們，可享受免費重做。</li>
<li>重做僅限同一服務項目，更換其他項目需另外收費。</li>
<li>僅適用於結果與事先約定需求不符的情況。</li>
</ul>

<h2>5. 預付套餐/儲值卡</h2>
<ul>
<li>未使用的剩餘次數可申請退款，已使用次數將按原價重新計算。</li>
<li>已過期的套餐不予退款。</li>
<li>折扣套餐的退款金額按原價扣除已使用次數後計算。</li>
</ul>

<h2>6. 退款處理時間</h2>
<ul>
<li>信用卡支付：取消後3~5個工作日內退款。</li>
<li>現金/銀行轉帳：申請後5~7個工作日內退至指定帳戶。</li>
<li>點數支付：即時返還點數。</li>
</ul>

<h2>7. 其他事項</h2>
<ul>
<li>遇不可抗力或緊急情況，可另行協商。</li>
<li>本規定可能會在提前通知後進行修改。</li>
<li>如有疑問，請透過電話或訊息聯繫我們。</li>
</ul>
HTML;
    }

    private static function deContent(): string
    {
        return <<<'HTML'
<h2>1. Stornierungsbedingungen</h2>
<p>Um einen reibungslosen Service und faire Terminvergabe für alle Kunden zu gewährleisten, gelten folgende Stornierungsbedingungen.</p>
<table>
<thead>
<tr><th>Stornierungszeitpunkt</th><th>Erstattungsrate</th></tr>
</thead>
<tbody>
<tr><td>Mehr als 24 Stunden vor dem Termin</td><td>100% vollständige Erstattung</td></tr>
<tr><td>12–24 Stunden vor dem Termin</td><td>50% Erstattung</td></tr>
<tr><td>Weniger als 12 Stunden vor dem Termin</td><td>Keine Erstattung</td></tr>
<tr><td>Nichterscheinen (No-Show)</td><td>Keine Erstattung</td></tr>
</tbody>
</table>

<h2>2. No-Show-Richtlinie</h2>
<p>Wenn Sie ohne vorherige Benachrichtigung nicht zum vereinbarten Termin erscheinen, wird dies als No-Show behandelt.</p>
<ul>
<li>Bei No-Show wird keine Erstattung gewährt.</li>
<li>Wiederholtes Nichterscheinen kann zu Einschränkungen bei zukünftigen Buchungen führen.</li>
<li>Eine Verspätung von mehr als 10 Minuten kann zur Ablehnung oder Verkürzung der Behandlung führen.</li>
</ul>

<h2>3. Terminänderungen</h2>
<ul>
<li>Mehr als 24 Stunden vorher: Eine kostenlose Umbuchung ist möglich.</li>
<li>Weniger als 24 Stunden vorher: Änderungen sind nicht möglich; bitte stornieren und neu buchen.</li>
<li>Änderungen der Behandlung können vor Ort vorgenommen werden (Preisunterschiede können anfallen).</li>
</ul>

<h2>4. Unzufriedenheit nach der Behandlung</h2>
<ul>
<li>Bei Unzufriedenheit kontaktieren Sie uns bitte innerhalb von 7 Tagen für eine kostenlose Nachbehandlung.</li>
<li>Die Nachbehandlung ist auf dieselbe Leistung beschränkt; ein Wechsel zu einer anderen Behandlung ist kostenpflichtig.</li>
<li>Dies gilt nur, wenn das Ergebnis von der vorher vereinbarten Anforderung abweicht.</li>
</ul>

<h2>5. Prepaid-Pakete / Gutscheine</h2>
<ul>
<li>Nicht genutzte Sitzungen können erstattet werden; genutzte Sitzungen werden zum Normalpreis berechnet.</li>
<li>Abgelaufene Gutscheine sind nicht erstattungsfähig.</li>
<li>Der Erstattungsbetrag für Rabattpakete wird nach Abzug der genutzten Sitzungen zum Normalpreis berechnet.</li>
</ul>

<h2>6. Erstattungszeitraum</h2>
<ul>
<li>Kartenzahlung: Erstattung innerhalb von 3–5 Werktagen.</li>
<li>Barzahlung/Überweisung: Gutschrift innerhalb von 5–7 Werktagen auf Ihr angegebenes Konto.</li>
<li>Punktezahlung: Sofortige Gutschrift auf Ihr Punktekonto.</li>
</ul>

<h2>7. Sonstiges</h2>
<ul>
<li>Bei höherer Gewalt oder Notfällen können Ausnahmen vereinbart werden.</li>
<li>Diese Richtlinie kann nach vorheriger Ankündigung geändert werden.</li>
<li>Bei Fragen kontaktieren Sie uns bitte telefonisch oder per Nachricht.</li>
</ul>
HTML;
    }

    private static function esContent(): string
    {
        return <<<'HTML'
<h2>1. Política de Cancelación de Reservas</h2>
<p>Para garantizar un servicio fluido y un acceso justo a las citas para todos los clientes, aplicamos la siguiente política de cancelación.</p>
<table>
<thead>
<tr><th>Momento de cancelación</th><th>Tasa de reembolso</th></tr>
</thead>
<tbody>
<tr><td>Más de 24 horas antes de la cita</td><td>100% reembolso total</td></tr>
<tr><td>12–24 horas antes de la cita</td><td>50% de reembolso</td></tr>
<tr><td>Menos de 12 horas antes de la cita</td><td>Sin reembolso</td></tr>
<tr><td>No presentarse (No-Show)</td><td>Sin reembolso</td></tr>
</tbody>
</table>

<h2>2. Política de No-Show</h2>
<p>Si no se presenta a su cita sin previo aviso, se considerará como no-show.</p>
<ul>
<li>Las citas con no-show no son reembolsables.</li>
<li>Los no-shows repetidos pueden resultar en restricciones para futuras reservas.</li>
<li>Llegar más de 10 minutos tarde puede resultar en la denegación del servicio o reducción del tiempo.</li>
</ul>

<h2>3. Cambios de Cita</h2>
<ul>
<li>Más de 24 horas antes: Se permite un cambio gratuito.</li>
<li>Menos de 24 horas antes: No se permiten cambios; cancele y reserve de nuevo.</li>
<li>Los cambios de servicio pueden realizarse en el local (pueden aplicarse diferencias de precio).</li>
</ul>

<h2>4. Insatisfacción Después del Servicio</h2>
<ul>
<li>Si no está satisfecho con los resultados, contáctenos dentro de los 7 días para una corrección gratuita.</li>
<li>La corrección se limita al mismo servicio; cambiar a otro servicio tendrá un costo adicional.</li>
<li>Solo aplica cuando el resultado difiere de lo acordado previamente.</li>
</ul>

<h2>5. Paquetes Prepago / Bonos</h2>
<ul>
<li>Las sesiones no utilizadas son reembolsables; las utilizadas se recalcularán al precio regular.</li>
<li>Los bonos vencidos no son reembolsables.</li>
<li>El reembolso de paquetes con descuento se calcula deduciendo las sesiones usadas al precio regular.</li>
</ul>

<h2>6. Tiempo de Procesamiento del Reembolso</h2>
<ul>
<li>Pago con tarjeta: Reembolso en 3–5 días hábiles.</li>
<li>Efectivo/transferencia: Depósito en su cuenta en 5–7 días hábiles.</li>
<li>Puntos: Acreditación inmediata en su cuenta.</li>
</ul>

<h2>7. Notas Adicionales</h2>
<ul>
<li>Se pueden hacer excepciones en caso de desastres naturales o emergencias.</li>
<li>Esta política puede actualizarse con previo aviso.</li>
<li>Para consultas, contáctenos por teléfono o mensaje.</li>
</ul>
HTML;
    }

    private static function frContent(): string
    {
        return <<<'HTML'
<h2>1. Conditions d'annulation</h2>
<p>Afin de garantir un service fluide et un accès équitable aux rendez-vous pour tous nos clients, nous appliquons les conditions d'annulation suivantes.</p>
<table>
<thead>
<tr><th>Moment de l'annulation</th><th>Taux de remboursement</th></tr>
</thead>
<tbody>
<tr><td>Plus de 24 heures avant le rendez-vous</td><td>Remboursement intégral à 100%</td></tr>
<tr><td>12 à 24 heures avant le rendez-vous</td><td>Remboursement à 50%</td></tr>
<tr><td>Moins de 12 heures avant le rendez-vous</td><td>Aucun remboursement</td></tr>
<tr><td>Absence (No-Show)</td><td>Aucun remboursement</td></tr>
</tbody>
</table>

<h2>2. Politique d'absence (No-Show)</h2>
<p>Si vous ne vous présentez pas à votre rendez-vous sans notification préalable, cela sera considéré comme une absence.</p>
<ul>
<li>Les absences ne sont pas remboursables.</li>
<li>Les absences répétées peuvent entraîner des restrictions sur les réservations futures.</li>
<li>Un retard de plus de 10 minutes peut entraîner un refus de service ou une réduction du temps de prestation.</li>
</ul>

<h2>3. Modification de rendez-vous</h2>
<ul>
<li>Plus de 24 heures avant : Un report gratuit est autorisé.</li>
<li>Moins de 24 heures avant : Les modifications ne sont pas possibles ; veuillez annuler et reprendre rendez-vous.</li>
<li>Les changements de prestation peuvent être effectués sur place (des différences de prix peuvent s'appliquer).</li>
</ul>

<h2>4. Insatisfaction après la prestation</h2>
<ul>
<li>En cas d'insatisfaction, contactez-nous dans les 7 jours pour une retouche gratuite.</li>
<li>La retouche est limitée à la même prestation ; tout changement de service entraînera des frais supplémentaires.</li>
<li>Cela s'applique uniquement lorsque le résultat diffère de votre demande préalable.</li>
</ul>

<h2>5. Forfaits prépayés / Bons</h2>
<ul>
<li>Les séances non utilisées sont remboursables ; les séances utilisées seront recalculées au tarif normal.</li>
<li>Les bons expirés ne sont pas remboursables.</li>
<li>Le montant de remboursement des forfaits à prix réduit est calculé après déduction des séances utilisées au tarif normal.</li>
</ul>

<h2>6. Délai de remboursement</h2>
<ul>
<li>Paiement par carte : Remboursement sous 3 à 5 jours ouvrables.</li>
<li>Espèces/virement : Virement sur votre compte sous 5 à 7 jours ouvrables.</li>
<li>Points : Crédit immédiat sur votre compte de points.</li>
</ul>

<h2>7. Autres informations</h2>
<ul>
<li>Des exceptions peuvent être faites en cas de force majeure ou d'urgence.</li>
<li>Cette politique peut être modifiée avec notification préalable.</li>
<li>Pour toute question, contactez-nous par téléphone ou message.</li>
</ul>
HTML;
    }

    private static function idContent(): string
    {
        return <<<'HTML'
<h2>1. Kebijakan Pembatalan Reservasi</h2>
<p>Untuk memastikan layanan yang lancar dan akses yang adil bagi semua pelanggan, kami menerapkan kebijakan pembatalan berikut.</p>
<table>
<thead>
<tr><th>Waktu Pembatalan</th><th>Tingkat Pengembalian</th></tr>
</thead>
<tbody>
<tr><td>Lebih dari 24 jam sebelum janji</td><td>Pengembalian penuh 100%</td></tr>
<tr><td>12–24 jam sebelum janji</td><td>Pengembalian 50%</td></tr>
<tr><td>Kurang dari 12 jam sebelum janji</td><td>Tidak ada pengembalian</td></tr>
<tr><td>Tidak hadir (No-Show)</td><td>Tidak ada pengembalian</td></tr>
</tbody>
</table>

<h2>2. Kebijakan Tidak Hadir (No-Show)</h2>
<p>Jika Anda tidak hadir pada waktu janji tanpa pemberitahuan sebelumnya, hal ini akan dianggap sebagai no-show.</p>
<ul>
<li>Janji no-show tidak dapat dikembalikan dananya.</li>
<li>No-show berulang dapat mengakibatkan pembatasan pemesanan di masa depan.</li>
<li>Keterlambatan lebih dari 10 menit dapat mengakibatkan penolakan layanan atau pengurangan waktu layanan.</li>
</ul>

<h2>3. Perubahan Janji</h2>
<ul>
<li>Lebih dari 24 jam sebelumnya: Satu kali penjadwalan ulang gratis diperbolehkan.</li>
<li>Kurang dari 24 jam sebelumnya: Perubahan tidak diperbolehkan; silakan batalkan dan pesan ulang.</li>
<li>Perubahan menu layanan dapat dilakukan di tempat (mungkin ada perbedaan harga).</li>
</ul>

<h2>4. Ketidakpuasan Setelah Layanan</h2>
<ul>
<li>Jika tidak puas dengan hasilnya, hubungi kami dalam 7 hari untuk pengerjaan ulang gratis.</li>
<li>Pengerjaan ulang terbatas pada layanan yang sama; perubahan ke layanan lain akan dikenakan biaya tambahan.</li>
<li>Ini hanya berlaku jika hasil berbeda dari permintaan yang telah disepakati.</li>
</ul>

<h2>5. Paket Prabayar / Voucher</h2>
<ul>
<li>Sesi yang belum digunakan dapat dikembalikan dananya; sesi yang telah digunakan akan dihitung ulang dengan harga normal.</li>
<li>Voucher yang telah kedaluwarsa tidak dapat dikembalikan.</li>
<li>Jumlah pengembalian untuk paket diskon dihitung setelah mengurangi sesi yang digunakan dengan harga normal.</li>
</ul>

<h2>6. Waktu Pemrosesan Pengembalian</h2>
<ul>
<li>Pembayaran kartu: Pengembalian dalam 3–5 hari kerja.</li>
<li>Tunai/transfer bank: Dikirim ke rekening Anda dalam 5–7 hari kerja.</li>
<li>Poin: Langsung dikreditkan kembali ke akun Anda.</li>
</ul>

<h2>7. Catatan Tambahan</h2>
<ul>
<li>Pengecualian dapat dibuat dalam kasus bencana alam atau keadaan darurat.</li>
<li>Kebijakan ini dapat diperbarui dengan pemberitahuan sebelumnya.</li>
<li>Untuk pertanyaan, silakan hubungi kami melalui telepon atau pesan.</li>
</ul>
HTML;
    }

    private static function mnContent(): string
    {
        return <<<'HTML'
<h2>1. Захиалга цуцлах журам</h2>
<p>Бүх үйлчлүүлэгчдэд тэгш боломж олгох, жигд үйлчилгээ үзүүлэх зорилгоор дараах цуцлалтын журмыг мөрдөж байна.</p>
<table>
<thead>
<tr><th>Цуцлах хугацаа</th><th>Буцаан олголт</th></tr>
</thead>
<tbody>
<tr><td>Цаг товлосноос 24 цагаас өмнө</td><td>100% бүтэн буцаалт</td></tr>
<tr><td>Цаг товлосноос 12–24 цагийн өмнө</td><td>50% буцаалт</td></tr>
<tr><td>Цаг товлосноос 12 цагийн дотор</td><td>Буцаалт байхгүй</td></tr>
<tr><td>Ирээгүй (No-Show)</td><td>Буцаалт байхгүй</td></tr>
</tbody>
</table>

<h2>2. Ирээгүй (No-Show) бодлого</h2>
<p>Урьдчилан мэдэгдэлгүйгээр товлосон цагтаа ирээгүй бол No-Show гэж тооцно.</p>
<ul>
<li>No-Show-ийн төлбөрийг буцаахгүй.</li>
<li>Давтан No-Show хийвэл цаашдын захиалга хязгаарлагдаж болно.</li>
<li>10 минутаас дээш хоцорвол үйлчилгээ үзүүлэхгүй эсвэл хугацаа богиносгож болно.</li>
</ul>

<h2>3. Захиалга өөрчлөх</h2>
<ul>
<li>24 цагаас өмнө: 1 удаа үнэгүй өөрчлөх боломжтой.</li>
<li>24 цагийн дотор: Өөрчлөх боломжгүй, цуцлаад дахин захиална уу.</li>
<li>Үйлчилгээний төрөл өөрчлөлтийг газар дээр нь хийх боломжтой (үнийн зөрүү гарч болно).</li>
</ul>

<h2>4. Үйлчилгээний дараа гомдол</h2>
<ul>
<li>Үр дүнд сэтгэл хангалуун бус бол 7 хоногийн дотор холбогдож, үнэгүй засвар хийлгэнэ үү.</li>
<li>Засвар зөвхөн ижил үйлчилгээнд хамаарна; өөр үйлчилгээнд нэмэлт төлбөр гарна.</li>
<li>Зөвхөн урьдчилж тохиролцсон хүсэлтээс ялгаатай үр дүнд хамаарна.</li>
</ul>

<h2>5. Урьдчилсан төлбөрт багц / Эрхийн бичиг</h2>
<ul>
<li>Ашиглаагүй удаагийн буцаалт боломжтой; ашигласан удааг хэвийн үнээр дахин тооцно.</li>
<li>Хугацаа дууссан эрхийн бичгийн буцаалт хийхгүй.</li>
<li>Хөнгөлөлттэй багцын буцаалтыг хэвийн үнээр тооцож хасалт хийсний дараа үлдсэн дүнг буцаана.</li>
</ul>

<h2>6. Буцаан олголтын хугацаа</h2>
<ul>
<li>Картын төлбөр: Цуцалснаас хойш 3–5 ажлын өдөрт.</li>
<li>Бэлэн мөнгө/шилжүүлэг: 5–7 ажлын өдөрт заасан дансанд.</li>
<li>Оноо: Шууд дансанд буцааж нэмнэ.</li>
</ul>

<h2>7. Бусад</h2>
<ul>
<li>Байгалийн гамшиг, онцгой байдлын үед тусгайлан зөвшилцөх боломжтой.</li>
<li>Энэ журам урьдчилан мэдэгдсэний дараа өөрчлөгдөж болно.</li>
<li>Асуулт байвал утас эсвэл мессежээр холбогдоно уу.</li>
</ul>
HTML;
    }

    private static function ruContent(): string
    {
        return <<<'HTML'
<h2>1. Правила отмены записи</h2>
<p>Для обеспечения качественного обслуживания и справедливого доступа к записи для всех клиентов действуют следующие правила отмены.</p>
<table>
<thead>
<tr><th>Время отмены</th><th>Возврат</th></tr>
</thead>
<tbody>
<tr><td>Более чем за 24 часа до визита</td><td>Полный возврат 100%</td></tr>
<tr><td>За 12–24 часа до визита</td><td>Возврат 50%</td></tr>
<tr><td>Менее чем за 12 часов до визита</td><td>Без возврата</td></tr>
<tr><td>Неявка (No-Show)</td><td>Без возврата</td></tr>
</tbody>
</table>

<h2>2. Политика неявки (No-Show)</h2>
<p>Если вы не явились на назначенное время без предварительного уведомления, это считается неявкой.</p>
<ul>
<li>Оплата за неявку не возвращается.</li>
<li>Повторные неявки могут привести к ограничению будущих записей.</li>
<li>Опоздание более чем на 10 минут может привести к отказу в обслуживании или сокращению времени процедуры.</li>
</ul>

<h2>3. Изменение записи</h2>
<ul>
<li>Более чем за 24 часа: Один бесплатный перенос.</li>
<li>Менее чем за 24 часа: Изменение невозможно; отмените и запишитесь заново.</li>
<li>Изменение вида услуги возможно на месте (может потребоваться доплата).</li>
</ul>

<h2>4. Неудовлетворённость после процедуры</h2>
<ul>
<li>Если вы не удовлетворены результатом, свяжитесь с нами в течение 7 дней для бесплатной коррекции.</li>
<li>Коррекция ограничена той же процедурой; смена услуги оплачивается дополнительно.</li>
<li>Применяется только если результат отличается от предварительно согласованного.</li>
</ul>

<h2>5. Предоплаченные пакеты / абонементы</h2>
<ul>
<li>Неиспользованные сеансы подлежат возврату; использованные пересчитываются по полной стоимости.</li>
<li>Абонементы с истёкшим сроком не возвращаются.</li>
<li>Сумма возврата по скидочным пакетам рассчитывается после вычета использованных сеансов по полной стоимости.</li>
</ul>

<h2>6. Сроки возврата</h2>
<ul>
<li>Оплата картой: Возврат в течение 3–5 рабочих дней.</li>
<li>Наличные/банковский перевод: Зачисление на указанный счёт в течение 5–7 рабочих дней.</li>
<li>Баллы: Мгновенное начисление на счёт.</li>
</ul>

<h2>7. Дополнительно</h2>
<ul>
<li>Исключения возможны в случае форс-мажора или чрезвычайных ситуаций.</li>
<li>Данная политика может быть изменена с предварительным уведомлением.</li>
<li>По вопросам обращайтесь по телефону или в мессенджер.</li>
</ul>
HTML;
    }

    private static function trContent(): string
    {
        return <<<'HTML'
<h2>1. Rezervasyon İptal Koşulları</h2>
<p>Tüm müşterilerimize sorunsuz hizmet sunmak ve randevulara adil erişim sağlamak amacıyla aşağıdaki iptal koşullarını uyguluyoruz.</p>
<table>
<thead>
<tr><th>İptal Zamanı</th><th>İade Oranı</th></tr>
</thead>
<tbody>
<tr><td>Randevudan 24 saatten fazla önce</td><td>%100 tam iade</td></tr>
<tr><td>Randevudan 12–24 saat önce</td><td>%50 iade</td></tr>
<tr><td>Randevudan 12 saatten az önce</td><td>İade yapılmaz</td></tr>
<tr><td>Gelmeme (No-Show)</td><td>İade yapılmaz</td></tr>
</tbody>
</table>

<h2>2. Gelmeme (No-Show) Politikası</h2>
<p>Önceden haber vermeden randevu saatinde gelmemeniz durumunda no-show olarak değerlendirilir.</p>
<ul>
<li>No-show durumunda ödeme iade edilmez.</li>
<li>Tekrarlayan no-show durumlarında gelecek rezervasyonlar kısıtlanabilir.</li>
<li>10 dakikadan fazla gecikme, hizmetin reddedilmesine veya süresinin kısaltılmasına neden olabilir.</li>
</ul>

<h2>3. Randevu Değişikliği</h2>
<ul>
<li>24 saatten fazla önce: Bir kez ücretsiz değişiklik yapılabilir.</li>
<li>24 saatten az önce: Değişiklik yapılamaz; lütfen iptal edip yeniden randevu alın.</li>
<li>Hizmet menüsü değişiklikleri yerinde yapılabilir (fiyat farkı uygulanabilir).</li>
</ul>

<h2>4. Hizmet Sonrası Memnuniyetsizlik</h2>
<ul>
<li>Sonuçtan memnun değilseniz, 7 gün içinde bizimle iletişime geçerek ücretsiz düzeltme yaptırabilirsiniz.</li>
<li>Düzeltme aynı hizmetle sınırlıdır; farklı bir hizmete geçiş ek ücrete tabidir.</li>
<li>Yalnızca sonuç, önceden mutabık kalınan talebinizden farklı olduğunda geçerlidir.</li>
</ul>

<h2>5. Ön Ödemeli Paketler / Kuponlar</h2>
<ul>
<li>Kullanılmayan seanslar iade edilebilir; kullanılan seanslar normal fiyat üzerinden yeniden hesaplanır.</li>
<li>Süresi dolmuş kuponlar iade edilemez.</li>
<li>İndirimli paketlerin iade tutarı, kullanılan seansların normal fiyattan düşülmesiyle hesaplanır.</li>
</ul>

<h2>6. İade İşlem Süresi</h2>
<ul>
<li>Kredi kartı ödemesi: İptalden sonra 3–5 iş günü içinde iade.</li>
<li>Nakit/banka havalesi: Belirttiğiniz hesaba 5–7 iş günü içinde aktarım.</li>
<li>Puan ödemesi: Anında puan olarak iade edilir.</li>
</ul>

<h2>7. Ek Bilgiler</h2>
<ul>
<li>Doğal afet veya acil durumlar için istisnalar yapılabilir.</li>
<li>Bu politika önceden bildirimde bulunularak güncellenebilir.</li>
<li>Sorularınız için telefon veya mesaj yoluyla bize ulaşabilirsiniz.</li>
</ul>
HTML;
    }

    private static function viContent(): string
    {
        return <<<'HTML'
<h2>1. Chính sách Hủy lịch hẹn</h2>
<p>Để đảm bảo dịch vụ suôn sẻ và quyền tiếp cận công bằng cho tất cả khách hàng, chúng tôi áp dụng chính sách hủy sau đây.</p>
<table>
<thead>
<tr><th>Thời điểm hủy</th><th>Tỷ lệ hoàn tiền</th></tr>
</thead>
<tbody>
<tr><td>Trước lịch hẹn hơn 24 giờ</td><td>Hoàn tiền 100%</td></tr>
<tr><td>Trước lịch hẹn 12–24 giờ</td><td>Hoàn tiền 50%</td></tr>
<tr><td>Trước lịch hẹn dưới 12 giờ</td><td>Không hoàn tiền</td></tr>
<tr><td>Không đến (No-Show)</td><td>Không hoàn tiền</td></tr>
</tbody>
</table>

<h2>2. Chính sách Không đến (No-Show)</h2>
<p>Nếu bạn không đến vào thời gian hẹn mà không thông báo trước, đó được coi là không đến.</p>
<ul>
<li>Các lịch hẹn không đến sẽ không được hoàn tiền.</li>
<li>Không đến nhiều lần có thể bị hạn chế đặt lịch trong tương lai.</li>
<li>Đến muộn hơn 10 phút có thể bị từ chối phục vụ hoặc giảm thời gian dịch vụ.</li>
</ul>

<h2>3. Thay đổi lịch hẹn</h2>
<ul>
<li>Trước hơn 24 giờ: Được phép đổi lịch miễn phí 1 lần.</li>
<li>Trước dưới 24 giờ: Không thể thay đổi; vui lòng hủy và đặt lại.</li>
<li>Thay đổi dịch vụ có thể thực hiện tại chỗ (có thể phát sinh chênh lệch giá).</li>
</ul>

<h2>4. Không hài lòng sau dịch vụ</h2>
<ul>
<li>Nếu không hài lòng với kết quả, vui lòng liên hệ trong vòng 7 ngày để được làm lại miễn phí.</li>
<li>Việc làm lại chỉ giới hạn cho cùng một dịch vụ; chuyển sang dịch vụ khác sẽ phát sinh thêm chi phí.</li>
<li>Chỉ áp dụng khi kết quả khác với yêu cầu đã thỏa thuận trước.</li>
</ul>

<h2>5. Gói trả trước / Voucher</h2>
<ul>
<li>Các buổi chưa sử dụng có thể được hoàn tiền; các buổi đã sử dụng sẽ được tính lại theo giá gốc.</li>
<li>Voucher đã hết hạn không được hoàn tiền.</li>
<li>Số tiền hoàn trả cho gói giảm giá được tính bằng cách trừ các buổi đã dùng theo giá gốc.</li>
</ul>

<h2>6. Thời gian xử lý hoàn tiền</h2>
<ul>
<li>Thanh toán bằng thẻ: Hoàn tiền trong 3–5 ngày làm việc.</li>
<li>Tiền mặt/chuyển khoản: Chuyển vào tài khoản chỉ định trong 5–7 ngày làm việc.</li>
<li>Điểm: Hoàn điểm ngay lập tức.</li>
</ul>

<h2>7. Lưu ý thêm</h2>
<ul>
<li>Có thể được miễn trừ trong trường hợp thiên tai hoặc tình huống khẩn cấp.</li>
<li>Chính sách này có thể được cập nhật với thông báo trước.</li>
<li>Mọi thắc mắc, vui lòng liên hệ qua điện thoại hoặc tin nhắn.</li>
</ul>
HTML;
    }
}
