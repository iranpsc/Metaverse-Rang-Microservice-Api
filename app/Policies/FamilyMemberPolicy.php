<?php

namespace App\Policies;

use App\Constants\FamilyMembersType;
use App\Models\Dynasty\FamilyMember;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class FamilyMemberPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can add new member.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function addFamilyMember(User $user, User $user_to_add, string $relationship)
    {
        $dynasty = $user->dynasty;

        if(empty($dynasty)) {
            abort(401, 'شما برای اضافه کردن عضو به سلسله ابتدا بایستی سلسه خود را تاسیس کنید');
        }

        $family = $dynasty->family;

        if(DB::table('family_members')->where('family_id', $family->id)
        ->where('user_id', $user_to_add->id)->exists()) {
            abort(401, 'این فرد قبلا عضو سلسله شما شده است');
        }

        if(DB::table('family_members')->where('user_id', $user_to_add->id)->exists()) {
            abort(401, 'این فرد قبلا عضو سلسله دیگری شده است');
        }


        $members = DB::table('family_members')->where('family_id', $family->id)->get();

        if($members->count() >= 11) {
            abort(401, 'تعداد مجاز عضوگیری این خانواده تکمیل شده است');
        }

        $brothers = $sisters = $ofsprings = 0;

        DB::table('family_members')->where('family_id', $family->id)->orderBy('relationship')->each(function($member) use($relationship, $brothers, $sisters, $ofsprings) {
            if($relationship === FamilyMembersType::FATHER && $member->relationship === $relationship) {
                abort(401, 'شما پدر خود را قبلا به سلسله خود اضافه کرده اید');
            }
            if($relationship === FamilyMembersType::MOTHER && $member->relationship === $relationship) {
                abort(401, 'شما مادر خود را قبلا به سلسله خود اضافه کرده اید');
            }

            if($relationship === FamilyMembersType::HUSBAND && $member->relationship === $relationship) {
                abort(401, 'شما شوهر خود را قبلا به سلسله خود اضافه کرده اید');
            }

            if($relationship === FamilyMembersType::WIFE && $member->relationship === $relationship) {
                abort(401, 'شما زن خود را قبلا به سلسله خود اضافه کرده اید');
            }

            if($member->relationship === FamilyMembersType::BROTHER) {
                if(($brothers + $sisters) >= 4 ) {
                    abort(401, 'شما تعداد حد مجاز اضافه کردن برادر و خواهر خود را به سلسله پر کرده اید');
                }
                $brothers+=1;
            }

            if($member->relationship === FamilyMembersType::SISTER) {
                if(($brothers + $sisters) >= 4 ) {
                    abort(401, 'شما تعداد حد مجاز اضافه کردن برادر و خواهر خود را به سلسله پر کرده اید');
                }
                $sisters+=1;
            }

            if($member->relationship === FamilyMembersType::OFFSPRING) {
                if($ofsprings >= 4)
                {
                    abort(401, 'شما تعداد حد مجاز اضافه کردن فرزند خود را به سلسله پر کرده اید');
                }
                $ofsprings+=1;
            }
        });

        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FamilyMember  $familyMember
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FamilyMember $familyMember)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FamilyMember  $familyMember
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FamilyMember $familyMember)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FamilyMember  $familyMember
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FamilyMember $familyMember)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FamilyMember  $familyMember
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FamilyMember $familyMember)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FamilyMember  $familyMember
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FamilyMember $familyMember)
    {
        //
    }
}
