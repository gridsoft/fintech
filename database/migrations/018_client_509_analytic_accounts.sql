-- Контен план за клиент 509: аналитички конта извлечени од нивниот excel
-- ("Lista na konta od finansovo analitika za firma 509.xls"). Официјалниот
-- MK контен план (seed.php, 384 конта) НЕ се менува/брише -- ова само
-- додава конта. Одлука на клиентот: нови аналитички конта како ДЕЦА на
-- официјалната база каде кодот се совпаѓа со официјален 3-цифрен код по
-- првите 3 цифри; каде не се совпаѓа (клиентската интерна нумерација отстапува
-- од официјалната), конто се внесува самостојно со сопствениот код, без
-- поврзување во официјалното дрво.
--
-- Извор: 377 конта чие име во изворот беше означено со "#" (единствените
-- што клиентот реално ги користи). Отфрлени: 5 конта чиј код веќе постои
-- официјално/од претходни migrations (1200, 2200, 2230, 7400, 810) -- не се
-- креираат повторно; 1 дупликат код (419001, задржан е првиот запис); 1 конто
-- со расипан извор-запис (код "#40301", име завршува на "НЕ ВАЗИ" -- самото
-- име го означува како невалидно, а се судираше со легитимното 40301).
-- Резултат: 371 конта.

-- 230 конта: код се совпаѓа со официјален 3-цифрен код по првите 3 цифри --
-- внесени како аналитички дете (parent_id), го наследуваат типот на родителот.
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0110011', 'ГРАДЕжЕН ОБЈЕКТ-БАРАКА 1- 473м2', 'asset', id, 1 FROM accounts WHERE code = '011';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0110012', 'ГРАДЕжЕН ОБЈЕКТ-БАРАКА 2- 322м2', 'asset', id, 1 FROM accounts WHERE code = '011';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0110013', 'ГРАДЕжЕН ОБЈЕКТ-БАРАКА 3,4,5- 602м2', 'asset', id, 1 FROM accounts WHERE code = '011';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '011900', 'ОБЈЕКТИ ЗА ДР.ПОТРЕБИ-КОНТЕЈНЕРИ-5', 'asset', id, 1 FROM accounts WHERE code = '011';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '011901', 'ОБЈЕКТИ ЗА ДР.ПОТРЕБИ-КОНТЕЈНЕР 1', 'asset', id, 1 FROM accounts WHERE code = '011';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '01200', 'ГЕНЕРАТОР', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012110', 'ЛАП ТОП', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012111', 'ТЕЛЕВИЗОР', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012113', 'КОМЈУТЕРСКА ОПРЕМА', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '01212', 'КОМПЈУТЕ.ОПРЕМА-СЕРВЕР ФАКОМ-КАСПЕР', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012150', 'УПС ,СВИчОВИ', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012318', 'ЗЕМЈОДЕЛСКА ОПРЕМА-ДЕЛ ЗА ТРАКТОР', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012319', 'ДРУГА ЗЕМЈОДЕЛСКА ОПРЕМА', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012320', 'ДРУГА ЗЕМЈОДЕЛСКА МАшИНА-ЛОПАТКА ЗА ОТКОПУВАЊЕ', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012321', 'БАГЕР', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0123241', 'ФРИжИДЕР БЕКО', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012325', 'МАшИНА ЗА САДОВИ УГРАДНА', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0123251', 'МАшИНА ЗА САДОВИ', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '012329', 'КАФЕМАТ ГАЏИА АНИМА', 'asset', id, 1 FROM accounts WHERE code = '012';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013065', 'ПАТНИчКО ВОЗИЛО-ОПЕЛ КОМБО', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013066', 'ПАТНИчКО ВОЗИЛО ХЈУНДАИ', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013068', 'ПАТНИчКО ВОЗИЛО-ФОЛЦФАГЕН ПОЛО', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013069', 'ПАТНИчКО ВОЗИЛО шКОДА ФАБИА', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013070', 'ТОВАРНО ВОЗИЛО-МАХИНДРА', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013071', 'ТОВАРНО ВОЗИЛО МАХИНДРА-КАФЕНО', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013252', 'ВИЛЈУшКАР ТОЈОТА', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013411', 'ПРИНТЕРИ', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013414', 'МОБИЛЕН РАчЕН СКЕНЕР', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013421', 'ФОТОКОПИР', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013436', 'ПРИНТЕР КАНОН', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013437', 'МОБИЛЕН ТЕЛЕФОН', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013438', 'ФИСКАЛЕН АПАРАТ', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013491', 'УРЕД ЗА ВИДЕО НАДЗОР', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013504', 'КАНЦЕЛАРИСКИ МЕБЕЛ', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013506', 'МЕБЕЛ ИЛИНДЕН', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013600', 'КЛИМА УРЕД', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '013700', 'БОЛЈЕР АРИСТОН 15 Л', 'asset', id, 1 FROM accounts WHERE code = '013';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0161', 'ГРАДЕжНИ ОБЈЕКТИ-ИНВЕСТИЦИИ ВО ТЕК-ПЕТРОВЕЦ', 'asset', id, 1 FROM accounts WHERE code = '016';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0162', 'ИНВЕСТИЦИИ ВО ТЕК-ФАКОМ', 'asset', id, 1 FROM accounts WHERE code = '016';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0163', 'ИНВЕСТИЦИИ ВО ТЕК-КОНТЕЊЕР ФАКОМ', 'asset', id, 1 FROM accounts WHERE code = '016';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0190', 'АКУМУЛ. АМОРТИЗ. НА ГРДЕжНИ ОБЈЕКТИ', 'asset', id, 1 FROM accounts WHERE code = '019';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0191', 'АКУМУЛ. АМОРТИЗ. НА ПОСТРОЈКИ И ОПРЕМА', 'asset', id, 1 FROM accounts WHERE code = '019';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0192', 'АКУМУ. АМОРТ. НА АЛАТ, КАНЦЕЛ. ИНВЕН, МЕБЕЛ И ТРАНС С-ВА', 'asset', id, 1 FROM accounts WHERE code = '019';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '0473', 'ПОБАРУВАЊА ПО ЦЕСИЈА И АСИГНАЦИЈА-БОРЕЦ', 'asset', id, 1 FROM accounts WHERE code = '047';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1001', 'жИРО СМЕТКА-КОМЕРЦИЈАЛНА БАНКА', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1002', 'жИРО СМЕТКА-СТОПАНСКА БАНКА', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1003', 'жИРО СМЕТКА -ТУТУНСКА БАНКА', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1004', 'жИРО СМЕТКА ХАЛК БАНКА', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1005', 'ПРЕОДНА СМЕТКА ДЕНАРИ-ОД БАНКА ВО БАНКА', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1007', 'ПРЕОДНА СМЕТКА ДЕВИЗИ', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1008', 'ОТКУП НА ДЕВИЗИ-ТРАНСФЕР ВО ДЕНАРИ', 'asset', id, 1 FROM accounts WHERE code = '100';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1011', 'ПРЕОДНА СМЕТКА ДОЛАРИ', 'asset', id, 1 FROM accounts WHERE code = '101';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1020', 'БЛАГАЈНА', 'asset', id, 1 FROM accounts WHERE code = '102';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '102200', 'БЛАГАЈНА ДНЕВЕН ПАЗАР', 'asset', id, 1 FROM accounts WHERE code = '102';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '102201', 'БЛАГАЈНА ИЛИНДЕН', 'asset', id, 1 FROM accounts WHERE code = '102';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1030', 'ДЕВИЗНА СМЕТКА -КОМЕРЦИЈАЛНА БАНКА', 'asset', id, 1 FROM accounts WHERE code = '103';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '10301', 'ДЕВИЗНА СМЕТКА КОМЕРЦИЈАЛНА БАНКА УСД', 'asset', id, 1 FROM accounts WHERE code = '103';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '10334', 'ХАЛК ДЕПОЗИТ 10.000.000.00', 'asset', id, 1 FROM accounts WHERE code = '103';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1034', 'ДЕВИЗНА СМЕТКА ХАЛК БАНКА', 'asset', id, 1 FROM accounts WHERE code = '103';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1035', 'ДЕВИЗНА СМЕТКА шПАРКАСЕ БАНКА', 'asset', id, 1 FROM accounts WHERE code = '103';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '12002', 'УТУжЕНИ ПОБАРУВАЊА', 'asset', id, 1 FROM accounts WHERE code = '120';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '12004', 'СПОРНИ ПОБАРУВАЊА', 'asset', id, 1 FROM accounts WHERE code = '120';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1210', 'ПОБА. ОД КУПУВАчИ ОД СТРАНСТВО', 'asset', id, 1 FROM accounts WHERE code = '121';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '162000', 'ПАРИчНИ С-ВА ,ЗАЕМИ И ДЕПОЗИТИ СО НЕПОВРЗ.СУБЈЕКТИ', 'asset', id, 1 FROM accounts WHERE code = '162';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '19000', 'ОДН. ПЛАТЕ. ТРОшОЦИ ЗА НАБАВ. НА СТОКИ', 'asset', id, 1 FROM accounts WHERE code = '190';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '190000', 'ОДНАПРЕД ПЛАТЕНИ ПРЕМИИ ЗА ОСИГУРУВАЊЕ', 'asset', id, 1 FROM accounts WHERE code = '190';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '190002', 'ОДНАПРЕД ПЛАТЕНИ ТРОшОЦИ', 'asset', id, 1 FROM accounts WHERE code = '190';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1905', 'ДЕПОЗИТИ ЗА ЦАРИНСКИ ДАВАчКИ', 'asset', id, 1 FROM accounts WHERE code = '190';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '1906', 'ОДНАПРЕД ПЛАТЕНИ ЦАРИНИ -чЕКА РЕшЕНИЕ ОД ЦАРИНА', 'asset', id, 1 FROM accounts WHERE code = '190';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22002', 'СПОРНИ ПОБАРУВАЊА', 'liability', id, 1 FROM accounts WHERE code = '220';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22004', 'СПОРНИ ОБВРСКИ', 'liability', id, 1 FROM accounts WHERE code = '220';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22040', 'ОБВРСКИ ПО ОСНОВ НА ДОГОВОР НА ДЕЛО', 'liability', id, 1 FROM accounts WHERE code = '220';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2210', 'ОБВРСКИ КОН СТРАНСКИ ДОБАВУВАчИ', 'liability', id, 1 FROM accounts WHERE code = '221';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2220', 'ПОБАРУВАЊЕ ПО ОСНОВ НА ДОГОВОР НА ДЕЛО', 'liability', id, 1 FROM accounts WHERE code = '222';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22200', 'ОБВРСКИ ЗА ПРИМЕНИ АВАНСИ-ВО ЗЕМЈАТА', 'liability', id, 1 FROM accounts WHERE code = '222';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22201', 'АВАНСИ ЗА МАТЕРИЈАЛНИ ОСНОВНИ СРЕДСТВА', 'liability', id, 1 FROM accounts WHERE code = '222';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22204', 'ОБВРСКИ ЗА ПРИМЕНИ АВАНСИ-ВО ЗЕМЈАТА ФАКОМ 2025', 'liability', id, 1 FROM accounts WHERE code = '222';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2221', 'ОБВРС. ЗА ПРИМЕНИ ПОЗАЈМИЦИ ОД ФИЗИ. ЛИЦА', 'liability', id, 1 FROM accounts WHERE code = '222';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '22300', 'ОБВРСКИ ЗА ПРИМЕНИ АВАНСИ ОД СТР. КУПУВАчИ', 'liability', id, 1 FROM accounts WHERE code = '223';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2400', 'ОБВРСКИ ЗА  НЕТО ПЛАТА', 'liability', id, 1 FROM accounts WHERE code = '240';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '24001', 'ОДБИТОЦИ ОД ПЛАТА', 'liability', id, 1 FROM accounts WHERE code = '240';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2422', 'РЕГРЕС ЗА ГОДИшЕН ОДМОР', 'liability', id, 1 FROM accounts WHERE code = '242';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '242901', 'ОСТАН. ОБВР ПРЕМА ВРАБ. ВО ДБ', 'liability', id, 1 FROM accounts WHERE code = '242';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '249201', 'ОБРВРСКИ СПРЕМА ВРАБОТЕНИ ЗА ЗАДРшКА ОД ПЛАТА', 'liability', id, 1 FROM accounts WHERE code = '249';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2620', 'КРАТК. КРЕДИТИ - ТЕЛЕФОН НА РАТИ', 'liability', id, 1 FROM accounts WHERE code = '262';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2900', 'ЦАРИНСКИ ДАВАчКИ-РЕФЕР.БРОЈ', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2901', 'ПРЕФАК. БАНКАРСКА ГАРАНЦИЈА', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '29011', 'ГАРАНЦИЈА-чЕКА РЕшЕНИЕ ОД ЦАРИНА', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2902', 'ЦАРИНСКИ ДАВАчКИ-КОПИМ', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2904', 'ЦАРИНСКИ ДАВАчКИ ВРАТЕНИ ОД ЦАРИНА ПО РЕшЕНИЕ', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2905', 'ЦАРИНСКИ ДАВАчКИ-ДЕПОЗИТ', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2909', 'ПРЕФАКТУРИРАЊЕ НА УВОЗНИ ДАВАчКИ', 'liability', id, 1 FROM accounts WHERE code = '290';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '351000', 'СИТЕН ИНВЕНТАР ВО УПОТРЕБА', 'asset', id, 1 FROM accounts WHERE code = '351';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '40103', 'ПОТРО. КАНЦЕЛ. МАТЕРИЈАЛ', 'expense', id, 1 FROM accounts WHERE code = '401';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '401090', 'ОСТАНАТИ МАТЕРИЈАЛИ-ЕЦД,ЦИМ', 'expense', id, 1 FROM accounts WHERE code = '401';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4011', 'ПОТРОш. МАТЕРИ. ЗА ТЕКОВНО ОДРжУВАЊЕ', 'expense', id, 1 FROM accounts WHERE code = '401';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4012', 'ТРОшОЦИ ЗА ОДРжУВ. ХИГИЕНА', 'expense', id, 1 FROM accounts WHERE code = '401';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4014', 'ТРОшОЦИ ЗА УНИФОРМИ И ЗАшТИТНА ОБЛЕКА', 'expense', id, 1 FROM accounts WHERE code = '401';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4050', 'ПОТР. РЕЗЕРВНИ ДЕЛОВИ ЗА СЕРВИСНИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '405';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '40520', 'ПОТР. РЕЗЕРВ. ДЕЛОВИ ЗА ТЕКОВ. ОДРжУВАЊЕ', 'expense', id, 1 FROM accounts WHERE code = '405';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '40521', 'ПОТР. РЕЗЕРВНИ ДЕЛОВИ ЗА МАшИНИ', 'expense', id, 1 FROM accounts WHERE code = '405';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4100', 'ТРАНСПОРТНИ УСЛУГИ ВО ЗЕМЈАТА', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41001', 'ТРАНСПОРТ ДОМАшЕН НА НАФТЕНИ ДЕРИВАТИ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '410011', 'ТРАНСПОРТ ДОМАшЕН НА ЈАГЛЕН', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41002', 'ЛОКАЛЕН ТРАНСПОРТ-КОН СТРАНСТВО', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41003', 'ДРУГИ ВИДОВИ ТРАНСПОРТ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41004', 'ДРУГИ ВИДОВИ ТРАНСПОРТ ВО ЗЕМЈАТА', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4101', 'ТРАНСПОРТНИ УСЛУГИ ВО СТРАНСТВО', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41010', 'ТРАНСПОРТНИ УСЛУГИ ДРУГИ-ДОЗВОЛИ И СЛ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41011', 'шПЕДИТЕРСКИ УСЛУГИ-СТРАНСТВО', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41012', 'шПЕДИТЕРСКИ УСЛУГИ ВО ЗЕМЈАТА', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4102', 'ТРАНСПОРТНИ УСЛУГИ-БИЛЕТИ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4103', 'ТРОшОЦИ ЗА жЕЛЕЗНИчКИ ТРАНСПОРТ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4104', 'ТРОшОЦИ ЗА Т1', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4106', 'ТРОшОЦИ ЗА АВИОНСКИ ТРАНСПОРТ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4107', 'РЕНТАКАР УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4109', 'ДРУГИ ТРАНСПОРТНИ УСЛУГИ-СТРАНСКИ', 'expense', id, 1 FROM accounts WHERE code = '410';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4110', 'трошоци за царински услуги', 'expense', id, 1 FROM accounts WHERE code = '411';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41100', 'ТРОшОЦИ ЗА ПОшТЕНСКИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '411';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41101', 'ТРОшОЦИ ЗА ФИКСНА ТЕЛЕФОНИЈА', 'expense', id, 1 FROM accounts WHERE code = '411';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41102', 'ТРОшОЦИ ЗА ИНТЕРНЕТ', 'expense', id, 1 FROM accounts WHERE code = '411';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41103', 'ТРОшОЦИ ЗА МОБИЛНА ТЕЛЕФОНИЈА', 'expense', id, 1 FROM accounts WHERE code = '411';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4130', 'УСЛУГИ ЗА ТЕКОВНО ОДРжУВАЊЕ', 'expense', id, 1 FROM accounts WHERE code = '413';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41301', 'УСЛУГИ ЗА ТЕКОВНО ОДРжУВАЊЕ-ФАКОМ', 'expense', id, 1 FROM accounts WHERE code = '413';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4139', 'ТРОшОЦИ ЗА ОДРжУВАЊЕ НА ХАРДВЕР', 'expense', id, 1 FROM accounts WHERE code = '413';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4140', 'КОРИСТЕЊЕ НА ГПС/ГСМ', 'expense', id, 1 FROM accounts WHERE code = '414';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41400', 'КИРИЈА-ФАКОМ', 'expense', id, 1 FROM accounts WHERE code = '414';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '414002', 'КИРИЈА-Мж ИНФРАКСТРУКТУРА', 'expense', id, 1 FROM accounts WHERE code = '414';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '414003', 'КИРИЈА ДЕНИ', 'expense', id, 1 FROM accounts WHERE code = '414';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '414004', 'КИРИЈА-ПАРКИНГ', 'expense', id, 1 FROM accounts WHERE code = '414';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '414005', 'КИРИЈА ТОВАРНА', 'expense', id, 1 FROM accounts WHERE code = '414';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41900', 'ОСТАНАТИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '419000', 'ОСТАНАТИ УСЛУГИ ПОВРЗАНИ СО ТРАНСПОРТОТ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '419001', 'ОСТАН. УСЛУГИ ПОВР. СО ТРАНСПО-КОНТЕЈНЕРСКИ ДО ДДВ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '419002', 'ОСТАН. ТРОш ПОВРЗ СО ТРАНСПОРТ-КОНТЕЈНЕРСКИ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41901', 'ДР. ТРОш. ЗА РЕГИСТРАЦИЈА', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '419010', 'ТРОшО. ЗА РЕГИСТР. НА ПАТНИчКИ ВОЗИЛА', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41903', 'ОСТАНАТИ УСЛУГИ - РЕНОВИРАЊЕ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '41909', 'ДР. ТРОш ЗА РЕГИСТ- ТЕХНИчКИ ПРЕГЛЕД', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4191', 'СЕРВИСНИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4192', 'УСЛУГИ ЗА КОРИС. НА ПАТИшТА- ПАТАРИНА', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4195', 'ТРОшОЦИ ЗА КОРИСТЕЊЕ НА КАСПЕР ПРОГРАМ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4196', 'ТРОшОЦИ ЗА ОДРжУВАЊЕ НА ХЕЛИКС', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '419630', 'ОСТАНАТИ УСЛУГИ - ОБЕЗБЕДУВАЊЕ', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4197', 'ТРОш. ЗА ОДРж. НА МРЕжА', 'expense', id, 1 FROM accounts WHERE code = '419';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '42100', 'БРУТО ПЛАТИ', 'expense', id, 1 FROM accounts WHERE code = '421';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4211', 'ОДБИТОЦИ ОД ПЛАТА', 'expense', id, 1 FROM accounts WHERE code = '421';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4220', 'ТРОшОЦИ ЗА ОТПРЕМНИНА ЗА ВРАБОТЕНИ-ПЕНЗИЈА', 'expense', id, 1 FROM accounts WHERE code = '422';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '422100', 'ТРОшОЦИ ЗА РЕГРЕС ЗА ГОД.ОДМОР', 'expense', id, 1 FROM accounts WHERE code = '422';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '42244', 'ПОМОш ЗА ЛЕКУВАЊЕ- ВО ДБ', 'expense', id, 1 FROM accounts WHERE code = '422';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '42290', 'СИСТЕМАТСКИ ПРЕГЛЕД НА ВРАБОТЕНИ', 'expense', id, 1 FROM accounts WHERE code = '422';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '42291', 'ЗДРАВСТВЕНИ УСЛУГИ - ковид тест', 'expense', id, 1 FROM accounts WHERE code = '422';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '42292', 'ДР. ТРОш. ЗА ВРАБОТЕНИ-ЛЕКУВАЊЕ', 'expense', id, 1 FROM accounts WHERE code = '422';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4320', 'ТРОшОЦИ ЗА АМОРТИЗАЦИЈА', 'expense', id, 1 FROM accounts WHERE code = '432';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4400', 'ТРОшОЦИ ЗА РЕКЛАМА', 'expense', id, 1 FROM accounts WHERE code = '440';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4401', 'ТРОш. ЗА СМЕСТ. НА СЛУ. ПАТ ВО СТРАНСТВО', 'expense', id, 1 FROM accounts WHERE code = '440';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4402', 'ТРОшО. ЗА СМЕСТУВ. НА СЛУж. ПАТ ВО ЗЕМЈАТА', 'expense', id, 1 FROM accounts WHERE code = '440';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4409', 'ДРУГИ ТРОшОЦИ ЗА СЛУжБЕНИ ПАТУВАЊА', 'expense', id, 1 FROM accounts WHERE code = '440';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '441900', 'ДР. НАДОМ. НА ТРО. НА ВРАБО. ВО ДБ', 'expense', id, 1 FROM accounts WHERE code = '441';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4430', 'ТРОшОЦИ ЗА СПОНЗОРСТВО И ДОНАЦИИ', 'expense', id, 1 FROM accounts WHERE code = '443';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4434', 'ДОНАЦИЈА НА ФИЗИчКО ЛИЦЕ-ВО ДБ', 'expense', id, 1 FROM accounts WHERE code = '443';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44362', 'ТРОш. ЗА ДОНАЦИЈА ВО СПОРТОТ чЛ.30-А(3)', 'expense', id, 1 FROM accounts WHERE code = '443';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4440', 'ТРОшОЦИ ЗА РЕПРЕЗЕНТАЦИЈА', 'expense', id, 1 FROM accounts WHERE code = '444';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '447001', 'ДАНОК НА ПОДИГ. МАТЕР. ТРОш. ВО ДБ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44701', 'ДАНОК НА ПРОМЕТ НА НЕДВИжНОСТИ-ФАКОМ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44702', 'ДАНОК НА ИМОТ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44732', 'чЛЕНАРИНИ ВО КОМОРИ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44740', 'КОМУНАЛНА ТАКСА ЗА ФИРМА', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44742', 'АДМИНИСТРАТИВНА ТАКСА', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44744', 'ИНСПЕКЦИСКИ УВИД', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44747', 'НАДОМЕСТ ЗА ЦАРИНСКИ ПРЕГЛЕД', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44761', 'чЛЕНАРИНА-КРЕДИТНИ КАРТИчКИ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44791', 'ТРОшОЦИ ЗА ТЕКОВНИ СОСТОЈБИ.Е-МАИЛ-ЦЕНТРАЛЕН РЕГИСТАР', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '447910', 'ПЕРСОНАЛЕН ДАНОК НА АВТОРСКИ ХОНОРАРИ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44793', 'ТРОш. ЗА ДР. ЈАВНИ ДАВАчКИ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44799', 'ОСТА. ДАВА.- ВО ДБ ПЕРСОНАЛЕН ПОЗАЈМИЦИ', 'expense', id, 1 FROM accounts WHERE code = '447';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4490', 'ТРОшОЦИ шТО СЕ ПРЕФАКТУРИРААТ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44900', 'ОСТАНАТИ ТРОшОЦИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44902', 'НОТАРСКИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '449020', 'ТРОшОЦИ ЗА ИЗВРшИТЕЛ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '449031', 'ТРОшОЦИ ЗА РЕВИЗИЈА', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44904', 'АДВОКАТСКИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44905', 'ТРОшОЦИ ЗА ТУжБИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44906', 'АВТОРСКИ ХОНОРАРИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44908', 'УСЛУГИ ПО ДОГОВОР НА ДЕЛО', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44909', 'ОСТАНАТИ ИНТЕЛЕКТУАЛНИ УСЛУГИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '449090', 'ДР. ИНТЕЛЕК. УСЛУГИ-ПРОЦЕНКА', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4491', 'ТРОшОЦИ шТО СЕ ПРЕФАК. СО ДДВ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4492', 'ТРОш. ПРЕФАКТУР. ВО СТРАНСТВО', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4493', 'СУДСКИ ТРОшОЦИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44940', 'НАДОМЕСТ ЗА СЕМИНАРИ И ОБУКИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '4495', 'Трош. за СТРУчНИ СПИСАНИЈА, ЛИТЕРАТУРА И ПЕчАТ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '44991', 'ОСТАНАТИ НЕМАТЕРИЈАЛНИ ТРОшОЦИ', 'expense', id, 1 FROM accounts WHERE code = '449';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '660001', 'НАБ. ВРЕД. НА ПРОД.  ВЕРНИ КАРТИчКИ', 'asset', id, 1 FROM accounts WHERE code = '660';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '660007', 'НАБА. ВРЕДНОСТ НА ПРОДАД. СТОКИ', 'asset', id, 1 FROM accounts WHERE code = '660';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '660008', 'СТОКИ ОД УВОЗ', 'asset', id, 1 FROM accounts WHERE code = '660';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '660009', 'ЈАГЛЕН', 'asset', id, 1 FROM accounts WHERE code = '660';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '6600091', 'ЈАГЛЕН СЕЛЕНИЦЕ', 'asset', id, 1 FROM accounts WHERE code = '660';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '660010', 'СТОКИ НА ЗАЛИХА 10%- ВЕРНА КАРТИчКИ', 'asset', id, 1 FROM accounts WHERE code = '660';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7010', 'НАБАВНА ВРЕДНОСТ НА ВЕРНА КАРТИчКИ', 'expense', id, 1 FROM accounts WHERE code = '701';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '701007', 'НАБАВ. ВРЕДНОСТ НА ПРОДАД. СТОКИ', 'expense', id, 1 FROM accounts WHERE code = '701';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '701008', 'НАБАВНА ВРЕДНОСТ НА СТОКИ ОД УВОЗ', 'expense', id, 1 FROM accounts WHERE code = '701';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '701009', 'НАБАВНА ВРЕДНОСТ НА ПРОДАДЕН ЈАГЛЕН', 'expense', id, 1 FROM accounts WHERE code = '701';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '70101', 'НАБАВНА ВРЕДНОСТ НА ПРОД. СТОКИ', 'expense', id, 1 FROM accounts WHERE code = '701';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '701010', 'НАБ. ВРЕД. НА ВЕРНА КАРТ. 10%', 'expense', id, 1 FROM accounts WHERE code = '701';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7039', 'НАБА. ВРЕД. НА ПРОДАДЕ. СТОКИ', 'expense', id, 1 FROM accounts WHERE code = '703';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7602', 'ДОБИВКА ОД ПРОДАжБА НА ТРАНСПОРТНИ С-ВА,ПОГОНСКИ ИНВЕНТАР', 'revenue', id, 1 FROM accounts WHERE code = '760';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7650', 'ОТПИС НА КРАТКОРОчНИ ОБВРСКИ ПРЕМА ДОБАВУВАчИ', 'revenue', id, 1 FROM accounts WHERE code = '765';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7740', 'КАМАТА НА ДЕПОЗИТ ПО ВИДУВАЊЕ', 'revenue', id, 1 FROM accounts WHERE code = '774';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7750', 'ПРИХО. ОД ПОЗИТИВНИ КУРСНИ РАЗЛИКИ', 'revenue', id, 1 FROM accounts WHERE code = '775';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '7760', 'ПРИХОДИ ОД ДИВИДЕНДИ ОД ОБИчНИ АКЦИИ', 'revenue', id, 1 FROM accounts WHERE code = '776';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '77901', 'ПРИХОДИ ОД ОДОБРЕН РАБАТ-МАКПЕТРОЛ', 'revenue', id, 1 FROM accounts WHERE code = '779';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '779010', 'ПРИХО. ОД ОДОБР. РАБАТ 10%-МАКПЕТРОЛ', 'revenue', id, 1 FROM accounts WHERE code = '779';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '77902', 'ПРИХОДИ ОД ОДОБРЕН РАБАТ -ДРУГИ', 'revenue', id, 1 FROM accounts WHERE code = '779';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '77903', 'ПРИХОД ОД ОДОБРЕН РАБАТ-СИМИТ ПЕТРОЛ', 'revenue', id, 1 FROM accounts WHERE code = '779';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '8000', 'ДОБИВКА ПРЕД ОДАНОчУВАЊЕ', 'equity', id, 1 FROM accounts WHERE code = '800';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '820000', 'НЕТО ДОБИВКА ПО ОДАНОчУВАЊЕ', 'equity', id, 1 FROM accounts WHERE code = '820';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '901000', 'ОСНОВЕН КАПИТАЛ', 'equity', id, 1 FROM accounts WHERE code = '901';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '940000', 'ЗАКОНСКА РЕЗЕРВА СО 31.12.2008', 'equity', id, 1 FROM accounts WHERE code = '940';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '940001', 'ЗАКОНСКИ РЕЗЕРВИ ПО 01.01.2009', 'equity', id, 1 FROM accounts WHERE code = '940';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '9420', 'ОСТАНАТИ РЕЗЕРВИ-АКЦИИ', 'equity', id, 1 FROM accounts WHERE code = '942';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '942200', 'ДРУГИ РЕЗЕРВИ-КАМИОНИ', 'equity', id, 1 FROM accounts WHERE code = '942';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '942201', 'ДРУГИ РЕЗЕРВИ ИНВЕСТИЦИЈА ВО 2015', 'equity', id, 1 FROM accounts WHERE code = '942';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '942202', 'ДРУГИ РЕЗЕРВИ ИНВЕСТИЦИЈА ВО 2023', 'equity', id, 1 FROM accounts WHERE code = '942';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '942204', 'ДР. РЕЗЕРВИ-ИНВЕСТИЦИЈА ВО 2025', 'equity', id, 1 FROM accounts WHERE code = '942';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '942205', 'ДР.РЕЗЕРВИ-ИНВЕСТИЦИЈА ВО 2026', 'equity', id, 1 FROM accounts WHERE code = '942';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '950001', 'ДОБИВКИ НЕОДА. ОД 2009-2011', 'equity', id, 1 FROM accounts WHERE code = '950';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '950003', 'НЕРАСПРЕДЕЛЕНА ДОБИВКА ПО 2014', 'equity', id, 1 FROM accounts WHERE code = '950';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '950025', 'НЕРАСПРЕДЕЛЕНА ДОБИВКА ЗА 2025', 'equity', id, 1 FROM accounts WHERE code = '950';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '951000', 'ДОБИВКА ОД ТЕКОВНА ГОДИНА', 'equity', id, 1 FROM accounts WHERE code = '951';

-- 141 конта: кодот не се совпаѓа со официјалната 3-цифрена нумерација --
-- внесени самостојно (parent_id = NULL), тип определен по класата (прва
-- цифра) на сопствениот код, по конвенцијата од seed.php.
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('03500', 'ОБИчНИ(РЕДОВНИ) АКЦИИ ОД НЕПОВРЗАНО ДРУшВО', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('0394', 'УСОГЛАСУВАЊЕ НА ФИНАНСИСКИ СРЕДСТВА-АКЦИИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1060', 'ТУТУНСКА БАНКА -СМЕТКА ДЕПОЗИТ 25000Е', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1061', 'ДЕПОЗИТ шПАРКАСЕ БАНКА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('10631', 'ДЕПОЗИТ ХАЛК БАНКА ЕУР КЛАУЗУЛА 145.562.37', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('10632', 'ДЕПОЗИТ ХАЛК БАНКА 359.500.00ДОПЛН.836.500.00 2023', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('106333', 'ДЕПОЗИТ ХАЛК БАНКА РАМКА 126.500.00', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('106334', 'ДЕПОЗИТ ХАЛК 10.000.000.00', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('106335', 'ДЕПОЗИТ ХАЛК 450.000.00', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('106336', 'ДЕПОЗИТ ХАЛК -700.000 ЕУР', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('106337', 'ДЕПОЗИТ ХАЛК 1.845.000-МАКСТИЛ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1069', 'ДЕПОЗИТИ КАЈ НЕПОВРЗАНИ НЕФИНАНСИСКИ ДРУшТВА ЗА РАЗНИ НАМЕН', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('122300', 'ДАДЕНИ АВАНСИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1223001', 'АВАНС ФАКОМ-2024', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('122301', 'ДАДЕНИ ПОЗАЈМИЦИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('122302', 'ДАДЕНИ ПОЗАЈМИЦИ КОН ФИЗИчКИ ЛИЦА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('122304', 'ДАДЕНИ АВАНСИ-ФАКОМ 2025', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1230', 'ДАДЕНИ АВАНСИ-КАВАЛЕТА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1259', 'ОСТАНАТИ ПОБАРУВАЊА ПО ОСНОВ НА КАМАТИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1279', 'ДРУГИ ОСТАНАТИ ПОБАРУВАЊА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1300', 'ПОБАР. ЗА ПОВЕ. ПЛАТЕН ДДВ -ПРЕТПЛАТА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('130005', 'ПОБАРУВА. ЗА ДДВ 5%', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('130010', 'ПОБАР. ЗА ДДВ 10%', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('130018', 'ПОБАР. ЗА ДДВ 18%', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1300181', 'ПРЕНЕСЕН ДДВ чЛ.32-А', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('130105', 'ПОБАР. ЗА ДДВ 5% УВОЗ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1320', 'ПОБАРУВАЊЕ ЗА ПОВЕќЕ ПЛАТЕНИ ЦАРИНИ И ЦАРИНСКИ ДАВАчКИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1330', 'ПОБАР. ЗА ПОВЕќЕ ПЛАТЕН ДАНОК', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('133000', 'ПОБАРУВАЊЕ ЗА ПОВЕќЕ ПЛАТЕН ДАНОК', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('145900', 'ОСТАНАТИ ПОБАРУВАЊА ОД ВРАБОТЕНИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('145901', 'ОСТАНАТИ ПОБАРУВАЊА ОД ВРАБОТЕНИ', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1989', 'ДР. ВРЕМЕНСКИ РАЗГРАНИчУВАЊА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2300', 'ОБВРСКИ ЗА ПЛАКАЊЕ НА ДДВ', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23005', 'ОБВР. ЗА ДДВ 5%', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23010', 'ОБВР. ЗА ДДВ 10%', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23018', 'ОБВРСКА ЗА ДДВ 18%', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('230181', 'ПРЕНЕСЕН ДДВ чЛ.32-А', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2330', 'ОБВРСКИ НА ДАНОК НА ДОБИВКА', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2340', 'ОБВРСКА ЗА ПЕРСОН. ДАНОК', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23400', 'ОБВРС.ЗА ПЕРСОНАЛЕН ДАНОК ОД ПЛАТА', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2342', 'ОБВРСКА ЗА  ПРИДОН. ПИО', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2344', 'ОБВРСКА ЗА ПРИД. ЗДРАВСТВО', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2345', 'ОБВРСКА ЗА ПРИД. ЗА ДОПОЛ. ЗДРАВСТВО', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2346', 'ОБВРСКА ЗА ПРИД. ЗА ВРАБОТУВАЊЕ', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23501', 'ОБВР. ЗА ПЕРС. ДАНОК ПО ДОГОВОР НА ДЕЛО', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23502', 'ПЕРСОНАЛЕН ДАНОК ЗА РЕПРЕЗЕНТАЦИЈА', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23504', 'ОБВРСКИ ЗА ПЕРСОНАЛЕН ПО ДОГОВОР НА ДЕЛО', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23505', 'ПЕРСОНАЛЕН ДАНОК ДРУГИ ЛИчНИ ПРИМАЊА', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('23507', 'ОБВРСКИ ЗА ПЕРСОНА. ДАНОК- ОСИГУР. жИВОТ', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2399', 'ДР. НЕСПОМ. ДАНОЦИ И ДР. ДАВАчКИ', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2951', 'ОДЛОж. ПРИХ. ЗАРАДИ РИЗИК НА НАПЛАТА (ПРИХОДИ ОД ТУжБИ)', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('355000', 'АВТОГУМИ ВО УПОТРЕБА', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('402011', 'КОМУНАЛНА ТАКСА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('403000', 'ПОТРОш. ЕЛЕКТРИчНА ЕНЕРГИЈА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('403001', 'ПОТРОшЕН ПЛИН', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('403006', 'ПОТРОшЕН БЕНЗИН', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('40301', 'ПОТРОш. ЕЛЕКТР. ЕНЕРГИЈА- ИЛИНДЕН', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('403011', 'ТРОш. ЗА КОМУН. ТАКСА - ЕВН', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('40302', 'ПОТРО. ЕЛЕКТР. ЕНЕРГИЈА- ПАРКИНГ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('40303', 'ПОТРОш. ЕЛ. ЕНЕРГИЈА- ќОЈЛИЈА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('40304', 'ПОТ.ЕЛЕКТ.ЕНЕГР-ФАКОМ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('40331', 'ПОТРш.УЉЕ ЗА ВОЗИЛА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4039', 'ПОТРОш. МАСЛО ЗА ГРЕЕЊЕ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('408010', 'ОТПИС НА СИТ. ИНВ. ВО УПОТРЕБА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('408210', 'ОТПИС НА АВТО ГУМИ ВО УПОТ.', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('41500', 'ТРОшОЦИ ЗА ВОДА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('41501', 'ТРОшОЦИ ЗА СМЕТ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4450', 'ПРЕМИИ ЗА ОСИГУ. НА ВРАБОТ.', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44500', 'ПРЕМИИ ЗА ОСИГ. НА ОБЈЕКТ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44502', 'ПРЕМИИ ЗА ОСИГ. НА ТРАНС. С-ВА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44520', 'ПРЕМИИ ЗА ОСИГ. НА ЛИЦА ПРИ РАБОТА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44590', 'ПРЕМИИ ЗА ОСИГУ. ПО ДРУГИ ОСНОВИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44591', 'ПРЕМИИ ЗА ОСИГУР. ВО ДБ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4460', 'БАНКАРСКА ПРОВИЗИЈА- ДЕНАРСКА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('446002', 'БАНКАРСКА ПРОВИЗИЈА- ДЕВИЗЕН ПРИЛИВ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('446005', 'ПРОВИЗИЈА ЗА ИЗВРшИТЕЛ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44601', 'БАНКАРСКА ПРОВИЗИЈА- ДЕВИЗНИ ОДЛИВИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44602', 'ПРОВИЗИЈА ЗА УЈП', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44603', 'МЕСЕчНА ПРОВИЗИЈА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44604', 'ПРОВИЗИЈА ЗА ЕЛЕКТРОНСКО БАНКАРСТВО', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44605', 'КАМАТА ПО БАН. ГАР. КОМЕРЦИЈАЛНА БАНКА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('44606', 'ПРОВИЗИЈА - ХАЛК БАНКА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4469', 'ДР. БАНК.УСЛУГИ-ТОКЕН', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4605', 'ЗАГУБА ОД ПРОДАж. НА МАТЕРИЈА. С-ВА', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('46051', 'ЗАГУБА ОД ПРОДА. НА ОСНОВ. СРЕД.', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4660', 'ДИРЕКТЕН ОТПИС НА ПОБАРУВАЊА ВО ЗЕМЈАТА 1200', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4670', 'РАСХОДИ ЗА ДОПОЛН. ОДОБРЕНИ ПОПУСТИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('46891', 'ДРУГИ КАЗНИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4693', 'РАСХОДИ ОД ПОРАНЕшНИ ГОДИНИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4750', 'РАСХ. ОД НЕГАТ. КУРСНИ РАЗЛИКИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4759', 'ТРОш. ЗА ПРОВИЗИЈА ОД  КУПОПРОДА. АКЦИИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('47910', 'КАЗНЕН. КАМАТИ ЗАРАД. НЕИСПОЛ. ДОГОВОРИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4792', 'КАМАТА ЗАРАДИ НЕПЛ. ЈАВНИ ДАВАчКИ', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74001', 'ПРИХОДИ ОД ПРОДАжБА НА ГУМИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740010', 'УСЛУГИ шТО СЕ ПРЕФАКТУРИРААТ СО ДДВ 10%', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740011', 'ПРИХОДИ ОД ПРОДАжБА НА ЈАГЛЕН', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74002', 'ПРИХ. ОД ПРОДА. НА ДОБРА И УСЛУ. ВО ЗЕМЈАТА БЕЗ ДДВ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740091', 'ПРИХ. ОД ПРОД.-ЈАГЛЕН АЛБАНИЈА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7401', 'ПРИХОДИ ОД УСЛУГИ -КОИ СЕ ПРЕФАКТУРИРААТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74010', 'ПРИХОД ОД ТРАНСПОРТ НА НАФТЕНИ ДЕРИВАТИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740100', 'ПРИХ.ОД ПРОДА. НА УСЛУГИ ВО ЗЕМЈАТА-ТРАНСПОРТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740101', 'ПРИХОДИ ОД ПРОДАжБА НА УСЛУГИ-ПАРКИНГ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740102', 'ПРИХОДИ ОД ПРОДАжБА НА УСЛУГИ-ПАРКИНГ (Ф)', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74011', 'УСЛУГИ шТО СЕ ПРЕФАКТУРИРААТ СО ДДВ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74013', 'ПРИХОД ОД УСЛУГИ ОД АВИОНСКИ ТРАНСПОРТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7402', 'ПРИХОДИ ОД БИЛЕТИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7403', 'ПРИХОДИ ОД шПЕДИТЕРСКИ УСЛУГИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74031', 'шПЕДИТЕРСКИ УСЛУГИ СО ДДВ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7404', 'ПРИХОДИ ОД НАФТА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740403', 'ПРИХОДИ ОД ПРЕФАКТ.- ВАУчЕР', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('740410', 'ПРИХОД. ОД НАФТА 10%', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7406', 'ПРИХОДИ ОД МАГАЦИНСКО РАБОТЕЊЕ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74061', 'ПРИХ. ОД ТРАНСПОРТ ЛОКАЛ НАшЕ КОМБЕ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74062', 'ПРИХОДИ ОД МАГАЦИНСКО РАБОТЕЊЕ СО ДДВ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7407', 'ПРИХОДИ ОД Т-1', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74070', 'ПРИХОДИ ОД ЕНС декларација', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74071', 'ПРИХОДИ ОД ТРАНЗИТЕН ДОКУМЕНТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7409', 'ПРИХ. ОД ПРОДАж. НА УСЛУГИ БЕЗ ДДВ- ПАРКИНГ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7410', 'ПРИХ. ОД ПРОДАж. НА СТОКИ ПО ОПш. СТАПКА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74100', 'ПРИХОДИ ОД НАФТА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74101', 'ПРИХОД ОД ПРОДАДЕНА СТОКА ОД УВОЗ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7420', 'ПРИХОДИ ОД ТРАНСПОРТНИ УСЛУГИ ВО СТРАНСТВО', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7421', 'ПРИХОДИ ОД ТРАНСПОРТНИ УСЛУГИ ВО ЗЕМЈАТА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74211', 'ПРИХОДИ ОД ТРАНСПОРТ НА ЈАГЛЕН', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('742191', 'ПРИХ. ОД ТРАНС.-ЈАГЛЕН АЛБАНИЈА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7422', 'ПРИХОДИ ОД жЕЛЕЗНИчКИ ТРАНСПОРТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7423', 'ПРИХОДИ ОД ДРУГ ВИД ТРАНСПОРТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74231', 'ПРИХ. ОД ТРАНСПОРТ-КОНТЕЈНЕРСКИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74232', 'ПРИХ. ОД ТРАНСП. КОНТЕЈНЕРСКИ СО ДДВ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74233', 'пРИХО. ОД КОНТЕЈНЕРСКИ СТРАНСТВО', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7476', 'ДР. ПРИХ. ОД НАЕМНИНА-МАшИНИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7493', 'ПРИХОДИ ОД ПРОДАжБА НА ОСНОВНИ СРЕДСТВА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7499', 'ДРУГ ПРИХОД ПОВРЗАН СО ТРАНСПОРТ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('74990', 'ПРИХ. ОД ПРОДА. НА ОСНОВНИ СРЕДСТВА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7692', 'ПРИХОДИ ОД ДРУГИ НАДОМЕСТОЦИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7693', 'ПРИХОД ОД ПОВРАТ НА ДАНОЦИ-СОЛИДАРЕН', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7694', 'ПРИХОДИ ОД НАПЛАТЕНИ ТУжБИ-КАМАТИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7695', 'ПРИХОДИ ОД ПРОВИЗИЈА ЗА БАНКАРСКА ГАРАНЦИЈА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('769518', 'ПРИХОДИ ОД ПРОВИЗИЈА ЗА БАНКАРСКА ГАРАНЦУЈА', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7696', 'ДОПОЛНИ. ПРИХОД ОД ПОРАНЕшНИ ГОДИНИ', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('76990', 'ПРИХОДИ ОД ОДОБРЕН РАБАТ-МАКПЕТРОЛ', 'revenue', NULL, 1);
